// Importar todas las declaraciones de tipos
import './components';

// Exportar tipos comunes
export interface User {
  id: number;
  name: string;
  email: string;
  avatar?: string;
  email_verified_at: string | null;
  created_at: string;
  updated_at: string;
}

export interface Auth {
  user: User;
}

export interface BreadcrumbItem {
  title: string;
  href: string;
}

export interface NavItem {
  title: string;
  href: string;
  icon?: any;
  isActive?: boolean;
  permission?: string;
  children?: NavItem[];
}

export interface SharedData {
  name: string;
  quote: { message: string; author: string };
  auth: Auth;
}

export type BreadcrumbItemType = BreadcrumbItem; 