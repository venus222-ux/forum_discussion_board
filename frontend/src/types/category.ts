//src/types/category.ts
export interface Category {
  id: number;
  name: string;
  slug: string;
  description?: string;
  threads_count?: number;
  parent_id?: number | null;
  is_active: boolean;
  display_order?: number;
  color?: string;
  icon?: string;
  children?: Category[];
}