import { Modal } from '@inertiaui/modal-react';
import { X } from 'lucide-react';
import type { ReactNode } from 'react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { ModalContainerContext } from '@/lib/modal-container';

type ModalFormProps = {
    title: string;
    description?: string;
    /**
     * Render prop receiving the modal's `close` callback so forms can dismiss
     * the slideover after a successful submit or when cancelling.
     */
    children: (close: () => void) => ReactNode;
};

/**
 * Right-side slideover shell for master-data create/edit forms. Provides a
 * header + scrollable body. The package close button is disabled so we can
 * render our own aligned with the header title.
 */
export function ModalForm({ title, description, children }: ModalFormProps) {
    const [panel, setPanel] = useState<HTMLElement | null>(null);

    return (
        <Modal
            slideover
            closeButton={false}
            panelClasses="bg-background text-foreground min-h-screen flex flex-col"
        >
            {({ close }: { close: () => void }) => (
                <ModalContainerContext.Provider value={panel}>
                    <div ref={setPanel} className="flex min-h-screen flex-col">
                        <header className="flex items-start justify-between gap-4 border-b px-5 py-5 sm:px-8 sm:py-6">
                            <div className="space-y-1">
                                <h2 className="text-lg font-semibold tracking-tight">
                                    {title}
                                </h2>
                                {description && (
                                    <p className="text-sm text-muted-foreground">
                                        {description}
                                    </p>
                                )}
                            </div>
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                onClick={close}
                                className="-mr-2 shrink-0 text-muted-foreground"
                                aria-label="Tutup"
                            >
                                <X className="size-5" />
                            </Button>
                        </header>
                        <div className="flex-1 overflow-y-auto px-5 py-5 sm:px-8 sm:py-6">
                            {children(close)}
                        </div>
                    </div>
                </ModalContainerContext.Provider>
            )}
        </Modal>
    );
}
