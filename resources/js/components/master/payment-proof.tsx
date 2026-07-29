import { Expand } from 'lucide-react';

/**
 * Clickable thumbnail preview of a payment proof. Renders the `thumb`
 * conversion when available and opens the full-size image in a new tab.
 */
export function PaymentProof({
    url,
    thumbUrl,
    className,
}: {
    url: string;
    thumbUrl?: string | null;
    className?: string;
}) {
    return (
        <a
            href={url}
            target="_blank"
            rel="noopener noreferrer"
            className={
                'group relative mt-2 block w-fit overflow-hidden rounded-md border transition-opacity hover:opacity-95' +
                (className ? ` ${className}` : '')
            }
            title="Lihat bukti pembayaran"
        >
            <img
                src={thumbUrl ?? url}
                alt="Bukti pembayaran"
                loading="lazy"
                className="size-24 object-cover"
            />
            <span className="absolute inset-0 flex items-center justify-center bg-black/0 text-transparent transition-colors group-hover:bg-black/40 group-hover:text-white">
                <Expand className="size-5" />
            </span>
        </a>
    );
}
