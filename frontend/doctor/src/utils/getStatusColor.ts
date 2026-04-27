import { STATUS_STYLES } from "./statusStyles";

export type StatusType = "appointment" | "session" | "report" | "payment";

export const getStatusColor = (type: StatusType, status?: string) => {
    const normalizedStatus = status?.toLowerCase();

  return (
    STATUS_STYLES[type][
      normalizedStatus as keyof (typeof STATUS_STYLES)[typeof type]
    ] || STATUS_STYLES[type].default
  );
};