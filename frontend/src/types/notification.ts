export interface NotificationData {
  [key: string]: unknown;
}

export interface NotificationItem {
  id: string;
  type: string;
  read_at: string | null;
  created_at: string;
  data: NotificationData;
}