# Docker deploy (ECR + EC2 + EFS)

App image: nginx + php-fpm + queue worker. **MySQL is external** (RDS or host). **Storage** is persisted outside the container.

## Storage layout

| Environment | Host path | Container path |
|-------------|-----------|----------------|
| Development (`docker compose`) | `./storage` (project tree) | `/var/www/html/storage` |
| Production | `/mnt/efs/mytask/storage` (EFS) | `/var/www/html/storage` |

Uploads, logs, framework cache/sessions/views live under `storage/`. Redeploying the container does not wipe them when the mount is in place.

## Local development

1. Point `.env` at external MySQL (or use `DB_HOST=host.docker.internal` for MySQL on the host).
2. Ensure `APP_KEY` is set.
3. Build and run:

```bash
docker compose build
docker compose up -d
```

App: `http://localhost:8080` (override with `APP_PORT`). Health: `http://localhost:8080/up`.

Compose forces `APP_URL=http://localhost:$APP_PORT` so Vite/Ziggy asset links match the browser. Using `.env`’s `:8000` URL inside Docker causes a blank page.

Optional first boot migrations:

```bash
RUN_MIGRATIONS=true docker compose up -d
```

## AWS prerequisites (one-time)

### ECR

1. Create repository named `mytask`.
2. Note registry URL: `<account>.dkr.ecr.<region>.amazonaws.com`.

### EC2

1. Install Docker Engine + Compose plugin.
2. Place deploy files on the host (e.g. `/opt/mytask`):
   - `docker-compose.yml`
   - `docker-compose.prod.yml`
   - `.env` (production secrets — never commit)
3. Security group: allow app → MySQL/RDS; inbound 80/443 (or ALB only).

### EFS

1. Create EFS in the same VPC; mount targets in the EC2 subnets.
2. On EC2:

```bash
sudo mkdir -p /mnt/efs/mytask
sudo mount -t efs -o tls fs-xxxxxxxx:/ /mnt/efs/mytask
sudo mkdir -p /mnt/efs/mytask/storage
# www-data in the image is typically uid/gid 33
sudo chown -R 33:33 /mnt/efs/mytask/storage
```

3. Persist mount in `/etc/fstab` (EFS mount helper or NFS).

## GitHub Actions secrets

| Secret | Purpose |
|--------|---------|
| `AWS_ACCESS_KEY_ID` | IAM user/role key with ECR push + (optional) describe |
| `AWS_SECRET_ACCESS_KEY` | Matching secret |
| `AWS_REGION` | e.g. `ap-southeast-1` |
| `ECR_REPOSITORY` | `mytask` |
| `EC2_HOST` | Public IP or DNS |
| `EC2_USER` | e.g. `ubuntu` / `ec2-user` |
| `EC2_SSH_KEY` | Private key for SSH deploy |
| `EC2_APP_DIR` | Optional; default `/opt/mytask` |

IAM needs at least `ecr:GetAuthorizationToken` and push/pull on the repository.

## Production bring-up on EC2

```bash
cd /opt/mytask
export ECR_REGISTRY=<account>.dkr.ecr.<region>.amazonaws.com
export IMAGE_TAG=latest
aws ecr get-login-password --region "$AWS_REGION" \
  | docker login --username AWS --password-stdin "$ECR_REGISTRY"
docker compose -f docker-compose.yml -f docker-compose.prod.yml pull
docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d
curl -fsS http://127.0.0.1/up
```

Set `RUN_MIGRATIONS=true` in the host `.env` for deploys that should migrate (default in prod compose override).

## CI/CD flow

[`.github/workflows/ci-cd.yml`](../.github/workflows/ci-cd.yml):

1. **Test** (every push / PR / manual run): PHPUnit via sqlite in-memory — no AWS secrets required  
2. **Deploy** (only after tests pass, on `main` or `workflow_dispatch`):
   - Build multi-stage Docker image  
   - Push tags: git SHA + `latest`  
   - SSH to EC2 → ECR login → compose pull/up → health check `/up`

PRs run **Test** only. Failed tests block deploy on `main`.

## Rollback

```bash
export IMAGE_TAG=<previous-sha>
docker compose -f docker-compose.yml -f docker-compose.prod.yml pull
docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d
```

Storage on EFS is unchanged across rollbacks.

## Notes

- Do not bake `.env` or `APP_KEY` into the image.
- TLS: terminate at ALB or host reverse proxy; container listens on HTTP 80.
- Queue worker runs inside the same container via Supervisor (`QUEUE_CONNECTION=database`).
