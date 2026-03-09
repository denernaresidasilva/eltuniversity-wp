# Status: Menu flutuante no plugin ZAP Tutor Events

## Resultado atual
O menu/navegação flutuante foi **removido** da interface administrativa do plugin.

## O que foi alterado

1. Remoção da renderização do bloco `.zap-floating-nav` em `includes/class-admin.php`.
2. Remoção dos estilos CSS relacionados a `.zap-floating-nav` em `assets/admin.css`.

## Impacto

- A navegação principal por abas permanece ativa (desktop e mobile).
- O restante da interface administrativa continua funcionando normalmente, sem as setas flutuantes de anterior/próxima.
