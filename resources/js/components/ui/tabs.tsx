import { createContext, ReactNode, useContext, useState } from 'react';
import { cn } from '@/lib/utils';

type TabsContextValue = {
    value: string;
    setValue: (value: string) => void;
};

const TabsContext = createContext<TabsContextValue | null>(null);

function useTabs(): TabsContextValue {
    const context = useContext(TabsContext);

    if (!context) {
        throw new Error('Tabs components must be used within <Tabs>');
    }

    return context;
}

export function Tabs({
    defaultValue,
    value: controlledValue,
    onValueChange,
    className,
    children,
}: {
    defaultValue: string;
    value?: string;
    onValueChange?: (value: string) => void;
    className?: string;
    children: ReactNode;
}) {
    const [uncontrolled, setUncontrolled] = useState(defaultValue);
    const value = controlledValue ?? uncontrolled;

    const setValue = (next: string) => {
        onValueChange?.(next);

        if (controlledValue === undefined) {
            setUncontrolled(next);
        }
    };

    return (
        <TabsContext.Provider value={{ value, setValue }}>
            <div className={className}>{children}</div>
        </TabsContext.Provider>
    );
}

export function TabsList({
    className,
    children,
}: {
    className?: string;
    children: ReactNode;
}) {
    return (
        <div
            role="tablist"
            className={cn(
                'inline-flex items-center gap-1 rounded-lg bg-muted p-1',
                className,
            )}
        >
            {children}
        </div>
    );
}

export function TabsTrigger({
    value,
    className,
    children,
}: {
    value: string;
    className?: string;
    children: ReactNode;
}) {
    const tabs = useTabs();
    const active = tabs.value === value;

    return (
        <button
            type="button"
            role="tab"
            aria-selected={active}
            onClick={() => tabs.setValue(value)}
            className={cn(
                'inline-flex items-center gap-2 rounded-md px-3 py-1.5 text-sm font-medium transition',
                active
                    ? 'bg-background text-foreground shadow-sm'
                    : 'text-muted-foreground hover:text-foreground',
                className,
            )}
        >
            {children}
        </button>
    );
}

export function TabsContent({
    value,
    className,
    children,
}: {
    value: string;
    className?: string;
    children: ReactNode;
}) {
    const tabs = useTabs();

    if (tabs.value !== value) {
        return null;
    }

    return (
        <div role="tabpanel" className={className}>
            {children}
        </div>
    );
}
