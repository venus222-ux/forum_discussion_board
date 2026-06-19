import { useQuery } from "@tanstack/react-query";
import API from "../api";
import type { Category } from "@/types";

const fetchCategories = async (): Promise<Category[]> => {
  const res = await API.get<Category[]>("/categories");
  return res.data;
};

export const useCategories = () => {
  return useQuery({
    queryKey: ["categories"],
    queryFn: fetchCategories,
    staleTime: 1000 * 60 * 5,
  });
};