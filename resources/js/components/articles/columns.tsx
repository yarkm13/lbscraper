"use client"

import { ColumnDef } from "@tanstack/react-table"

// This type is used to define the shape of our data.
// You can use a Zod schema here if you want.
export type Article = {
    id: string
    author: string
    date: Date
    title: string
    url: string
}

export const columns: ColumnDef<Payment>[] = [
    {
        accessorKey: "id",
        header: "#",
    },
    {
        accessorKey: "title",
        header: "Title",
    },
    {
        accessorKey: "author",
        header: "Author",
    },
    {
        accessorKey: "date",
        header: "Date",
    },
    {
        accessorKey: "url",
        header: "URL",
    },
]
