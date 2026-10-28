import { clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

/**
 * Merge Tailwind class names (shadcn-vue helper).
 * @param {...import('clsx').ClassValue} inputs
 */
export function cn(...inputs) {
    return twMerge(clsx(inputs));
}
