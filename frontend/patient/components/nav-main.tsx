"use client"

import Link from "next/link";
import { usePathname } from "next/navigation";
import type { ReactElement } from "react";
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from "@/components/ui/collapsible";
import { useSidebar } from "@/components/ui/sidebar";
import { ChevronRightIcon } from "lucide-react";
import React from "react";

type NavItem = {
  title: string;
  url?: string;
  icon?: ReactElement;
  items?: {
    title: string;
    url: string;
  }[];
};

export function NavMain({ items }: { items: NavItem[] }) {
  const pathname = usePathname();
  const { state } = useSidebar();

  const isCollapsed = state === "collapsed";

  const isActive = (url?: string) => {
    if (!url) return false;
    if (url === "/") return pathname === url;
    return pathname.startsWith(url);
  };

  return (
    <div className={`flex flex-col gap-2 ${isCollapsed ? "px-2" : "px-5"}`}>
      {items?.map((item) => {
        if (item.items && item.items.length > 0) {
          const isAnySubItemActive = item.items.some((subItem) => isActive(subItem.url));

          return (
            <Collapsible
              key={item.title}
              defaultOpen={isAnySubItemActive}
              className="group/collapsible"
            >
              <CollapsibleTrigger asChild>
                <button
                  className={`flex w-full items-center rounded-lg py-2 transition hover:bg-muted ${isCollapsed ? "justify-center px-2" : "justify-between"
                    }`}
                >
                  <div
                    className={`flex items-center text-sm ${isCollapsed ? "justify-center" : "gap-3 px-2"
                      }`}
                  >
                    {item.icon
                      ? React.cloneElement(item.icon as React.ReactElement<any>, {
                        className: "w-4 h-4",
                      })
                      : null}

                    {!isCollapsed && <span>{item.title}</span>}
                  </div>

                  {!isCollapsed && (
                    <ChevronRightIcon className="h-4 w-4 transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90" />
                  )}
                </button>
              </CollapsibleTrigger>

              {!isCollapsed && (
                <CollapsibleContent className="mt-1 flex flex-col gap-1 pl-4">
                  {item.items.map((subItem) => {
                    const active = isActive(subItem.url);

                    return (
                      <Link
                        key={subItem.title}
                        href={subItem.url}
                        className={`flex items-center gap-3 rounded-lg px-2 py-2 text-sm transition ${active
                            ? "border border-blue-200 bg-blue-50 text-blue-600"
                            : "hover:bg-muted"
                          }`}
                      >
                        <span>{subItem.title}</span>
                      </Link>
                    );
                  })}
                </CollapsibleContent>
              )}
            </Collapsible>
          );
        }

        const active = isActive(item.url);

        return (
          <Link
            key={item.title}
            href={item.url || "#"}
            className={`flex items-center rounded-md py-2 font-medium text-sm transition ${isCollapsed ? "justify-center px-2" : "gap-3 px-2"
              } ${active ? "text-primary border border-gray-200" : "hover:bg-muted"}`}
          >
            {item.icon
              ? React.cloneElement(item.icon as React.ReactElement<any>, {
                className: "w-4 h-4",
              })
              : null}

            {!isCollapsed && <span>{item.title}</span>}
          </Link>
        );
      })}
    </div>
  );
}