import { ChevronDownIcon } from "lucide-react"
import * as React from "react"

import { cn } from "@/lib/utils"

/**
 * `<select>` bawaan browser yang tampilannya disamakan dengan SelectTrigger.
 * Dipakai untuk form yang butuh nilai kosong ("Semua ...") atau daftar
 * panjang yang enak dipilih lewat picker native di HP.
 */
function NativeSelect({
  className,
  wrapperClassName,
  ...props
}: React.ComponentProps<"select"> & { wrapperClassName?: string }) {
  return (
    <div className={cn("relative w-full", wrapperClassName)}>
      <select
        data-slot="native-select"
        className={cn(
          "border-input dark:bg-input/30 dark:hover:bg-input/50 h-9 w-full min-w-0 cursor-pointer appearance-none rounded-md border bg-transparent py-1 pr-9 pl-3 text-sm shadow-xs transition-[color,box-shadow] outline-none disabled:cursor-not-allowed disabled:opacity-50",
          "focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]",
          "aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive",
          "[&_option]:bg-popover [&_option]:text-popover-foreground",
          className
        )}
        {...props}
      />
      <ChevronDownIcon className="text-muted-foreground pointer-events-none absolute top-1/2 right-3 size-4 -translate-y-1/2 opacity-50" />
    </div>
  )
}

export { NativeSelect }
