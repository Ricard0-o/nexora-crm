# Migrações históricas

Para instalação **nova**, importe somente `database/install.sql` uma vez em banco vazio.
O baseline incorpora 001, 005 e 006. Os campos de assinatura e a tabela de pagamentos
já estão presentes em 001; portanto, aplicar 003 depois de 001 causa colunas duplicadas.
002 e 004 foram mantidas para rastrear a evolução de instalações antigas.
Não execute esta pasta em lote e não reaplique o baseline em uma base existente.
Para atualização, compare o esquema e faça backup antes de definir as migrações necessárias.
