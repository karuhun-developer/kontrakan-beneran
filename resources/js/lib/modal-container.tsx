import { createContext, useContext } from 'react';

/**
 * DOM node of the current slideover/modal panel. Radix portalled overlays
 * (Select, Popover, Dropdown, …) render into this node so they stay inside the
 * modal's focus trap — otherwise opening them steals focus back and closes them
 * immediately. `null` when not inside a modal, so portals fall back to the body.
 */
export const ModalContainerContext = createContext<HTMLElement | null>(null);

export function useModalContainer(): HTMLElement | null {
    return useContext(ModalContainerContext);
}
