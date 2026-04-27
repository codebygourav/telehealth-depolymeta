import { ReactNode } from "react";

export interface NavItem {
  title: string;
  href: string;
  icon: ReactNode;
  badge?: string | number;
}
