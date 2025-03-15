"use client"

import { TrendingUp } from "lucide-react"
import { Bar, BarChart, CartesianGrid, XAxis } from "recharts"

import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from "@/components/ui/card"
import {
    ChartConfig,
    ChartContainer,
    ChartTooltip,
    ChartTooltipContent,
} from "@/components/ui/chart"
const chartData = [
    { month: "January", amount: 186},
    { month: "February", amount: 305 },
    { month: "March", amount: 237 },
    { month: "April", amount: 73,},
    { month: "May", amount: 209 },
    { month: "June", amount: 214 },
]

const chartConfig = {
    desktop: {
        label: "Amount",
        color: "hsl(var(--chart-1))",
    },
} satisfies ChartConfig

export function DashboardArticlesCard({total, stats}) {
    return (
        <Card>
            <CardHeader>
                <CardTitle>Articles</CardTitle>
                <CardDescription>Laravel News</CardDescription>
            </CardHeader>
            <CardContent>
                <ChartContainer config={chartConfig}>
                    <BarChart accessibilityLayer data={stats}>
                        <CartesianGrid vertical={false} />
                        <XAxis
                            dataKey="month"
                            tickLine={false}
                            tickMargin={10}
                            axisLine={false}
                            tickFormatter={(value) => value.slice(0, 3)}
                        />
                        <ChartTooltip
                            cursor={false}
                            content={<ChartTooltipContent indicator="dashed" />}
                        />
                        <Bar dataKey="amount" fill="var(--color-desktop)" radius={4} />
                    </BarChart>
                </ChartContainer>
            </CardContent>
            <CardFooter className="flex-col items-start gap-2 text-sm">
                <div className="flex gap-2 font-medium leading-none">
                    Total: {total}
                </div>
            </CardFooter>
        </Card>
    )
}
