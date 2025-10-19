import { useParams } from "react-router-dom";

export const useTenant = () => {
  const { tenant } = useParams<{ tenant: string }>();
  return tenant!;
};
