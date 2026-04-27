"use client";

import { useRouter } from "next/navigation";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";

export interface QuickActionItem {
  id: number | string;
  title: string;
  icon: React.ReactNode;
  href: string;
}

interface QuickActionsCardProps {
  title?: string;
  actions: QuickActionItem[];
}

export default function QuickActionsCard({
  title = "Quick Actions",
  actions,
}: QuickActionsCardProps) {
  const router = useRouter();

  return (
    <Card className="border-border">
      <CardHeader>
        <CardTitle>{title}</CardTitle>
      </CardHeader>

      <CardContent>
        <div className="grid gap-3 grid-cols-2 md:grid-cols-5 ">
          {actions.map((action) => (
            <Button
              key={action.id}
              variant="outline"
              className="h-auto flex-col gap-2 py-6 cursor-pointer"
              onClick={() => router.push(action.href)}
            >
              {action.icon}
              <span>{action.title}</span>
            </Button>
          ))}
        </div>
      </CardContent>
    </Card>
  );
}