"use client";

import { Card, CardContent } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Search, Filter } from "lucide-react";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";

interface AppointmentFiltersProps {
    searchQuery: string;
    selectedFilter: string;
    setSearchQuery: (value: string) => void;
    setSelectedFilter: (value: string) => void;
    statusOptions: { value: string; label: string }[];
}

export default function AppointmentFilters({
    searchQuery,
    selectedFilter,
    setSearchQuery,
    setSelectedFilter,
    statusOptions,
}: AppointmentFiltersProps) {
    return (
      
            <CardContent className="p-0">
                <div className="flex flex-col md:gap-4 gap-2 md:flex-row">
                    <div className="flex-1">
                        <div className="relative">
                            <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                placeholder="Search by patient name or reason..."
                                value={searchQuery}
                                onChange={(e) => setSearchQuery(e.target.value)}
                                className="pl-9"
                            />
                        </div>
                    </div>


                    <div className="flex gap-2 rounded">
                        <Select value={selectedFilter} onValueChange={setSelectedFilter}>
                            <SelectTrigger className="w-auto">
                                <Filter className="mr-2 h-4 w-4" />
                                <SelectValue placeholder="Filter by status" />
                            </SelectTrigger>
                            <SelectContent>
                                {statusOptions.map((status) => (
                                    <SelectItem key={status.value} value={status.value}>
                                        {status.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                </div>
            </CardContent>
 
    );
}