import * as React from 'react';

import { cn } from '@/lib/utils';

/**
 * Textarea gaya guru (sepadan field create/edit jurnal).
 */
function Textarea({ className, ...props }: React.ComponentProps<'textarea'>) {
    return (
        <textarea
            data-slot="textarea"
            className={cn(
                'min-h-20 w-full resize-none rounded-[10px] border border-[#E5E9F2] bg-white p-3 text-[13px] font-normal text-[#1A1D26] outline-none placeholder:text-[#9CA3AF] focus:border-[#2563EB] disabled:cursor-not-allowed disabled:opacity-50',
                className,
            )}
            {...props}
        />
    );
}

export { Textarea };
