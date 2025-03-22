// Declaraciones de tipo para componentes UI personalizados
declare module '@/components/ui/table' {
  import { DefineComponent } from 'vue';
  
  export const Table: DefineComponent<{}, {}, any>;
  export const TableBody: DefineComponent<{}, {}, any>;
  export const TableCaption: DefineComponent<{}, {}, any>;
  export const TableCell: DefineComponent<{}, {}, any>;
  export const TableHead: DefineComponent<{}, {}, any>;
  export const TableHeader: DefineComponent<{}, {}, any>;
  export const TableRow: DefineComponent<{}, {}, any>;
  export const TableFooter: DefineComponent<{}, {}, any>;
}

declare module '@/components/ui/badge' {
  import { DefineComponent } from 'vue';
  
  export const Badge: DefineComponent<{}, {}, any>;
}

declare module '@/components/ui/select' {
  import { DefineComponent } from 'vue';
  
  export const Select: DefineComponent<{}, {}, any>;
  export const SelectContent: DefineComponent<{}, {}, any>;
  export const SelectItem: DefineComponent<{}, {}, any>;
  export const SelectTrigger: DefineComponent<{}, {}, any>;
  export const SelectValue: DefineComponent<{}, {}, any>;
}

declare module '@/components/ui/textarea' {
  import { DefineComponent } from 'vue';
  
  export const Textarea: DefineComponent<{}, {}, any>;
}

declare module '@/components/ui/alert' {
  import { DefineComponent } from 'vue';
  
  export const Alert: DefineComponent<{}, {}, any>;
  export const AlertTitle: DefineComponent<{}, {}, any>;
  export const AlertDescription: DefineComponent<{}, {}, any>;
}

declare module '@/components/ui/button' {
  import { DefineComponent } from 'vue';
  
  export const Button: DefineComponent<{}, {}, any>;
}

declare module '@/components/ui/input' {
  import { DefineComponent } from 'vue';
  
  export const Input: DefineComponent<{}, {}, any>;
}

declare module '@/components/ui/label' {
  import { DefineComponent } from 'vue';
  
  export const Label: DefineComponent<{}, {}, any>;
}

declare module '@/components/ui/card' {
  import { DefineComponent } from 'vue';
  
  export const Card: DefineComponent<{}, {}, any>;
  export const CardContent: DefineComponent<{}, {}, any>;
  export const CardDescription: DefineComponent<{}, {}, any>;
  export const CardFooter: DefineComponent<{}, {}, any>;
  export const CardHeader: DefineComponent<{}, {}, any>;
  export const CardTitle: DefineComponent<{}, {}, any>;
} 