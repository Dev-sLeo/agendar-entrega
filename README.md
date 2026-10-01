# Agendar Entregas

Plugin para WooCommerce que adiciona agendamento de entregas no checkout, com turnos configuráveis, limite de vagas por dia, calendário administrativo e atualizações automáticas via GitHub.

## Requisitos

- WordPress 5.8+
- WooCommerce ativo
- PHP 7.4+

## Funcionalidades

### Checkout

- Campos de **data de entrega** e **turno** aparecem na revisão do pedido, logo após o método de entrega escolhido, e só ficam visíveis depois que o cliente seleciona um método de entrega.
- O turno mostra apenas o horário (`10:00 - 14:00`); a validação de vaga acontece via AJAX conforme a data é escolhida.
- Dias de espera para preparação do produto, configurável (ex.: com 1 dia de espera, um pedido feito hoje só pode ser entregue depois de amanhã).
- Bloqueio de datas específicas (feriados, manutenção) e de dias da semana recorrentes (ex.: nunca entregar aos domingos).
- Ajuste manual do limite de vagas de um turno numa data específica, sem alterar o limite padrão do turno (ex.: só 1 vaga disponível na quarta dia 30).
- Turnos podem ser vinculados a métodos de entrega específicos (frete por zona ou retirada no local) — cada endereço de retirada cadastrado em Configurações > Frete > Retirada no local vira uma opção própria.

### Admin

- **Entregas > Calendário**: visualização mensal ou em lista (FullCalendar, localizado em pt-br) dos pedidos agendados com status "processando" ou "concluído". Botões para atualizar o calendário e sincronizar pedidos antigos (recria agendamentos de pedidos que não passaram pelo checkout, ex.: pagamento na entrega).
- **Entregas > Turnos**: CRUD de turnos (nome, horário, limite de vagas, dias da semana, métodos de entrega), com edição inline e vínculo em grupo (associar vários turnos a um método de entrega de uma vez).
- **Entregas > Dias Bloqueados**: bloqueio por data específica ou por dia da semana recorrente, e ajuste manual de limite de vagas por data.
- **Entregas > Importar**: importação de dias bloqueados via arquivo CSV/XLSX ou sincronização periódica com uma planilha Google Sheets publicada como CSV.
- Meta box **"Agendamento de Entrega"** na edição do pedido, para agendar manualmente pedidos criados diretamente no admin (sem passar pelo checkout).

### Compatibilidade

- Funciona com o armazenamento de pedidos em tabelas próprias do WooCommerce (HPOS) e com o modelo clássico (Custom Post Type).

## Instalação

1. Baixe o `.zip` da [última release](https://github.com/Dev-sLeo/agendar-entrega/releases/latest).
2. No WordPress, vá em **Plugins > Adicionar novo > Enviar plugin** e envie o arquivo.
3. Ative o plugin (o WooCommerce precisa estar ativo).

## Atualizações automáticas via GitHub

O plugin usa a biblioteca [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker) (vendorizada em `includes/libs/plugin-update-checker`) para verificar as tags do repositório e injetar a atualização diretamente na tela **Painel > Plugins**, como se fosse uma atualização normal — sem depender do WordPress.org.

### Como publicar uma nova versão

1. Atualize o número da versão no cabeçalho de `agendar-entregas.php` (`Version:`) e na constante `AE_VERSION`.
2. Faça commit e push das alterações.
3. Crie e envie uma tag no GitHub com o mesmo número de versão (o prefixo `v`, ex. `v1.2.0`, é opcional):
   ```bash
   git tag v1.2.0
   git push origin v1.2.0
   ```
4. (Opcional) Publique também uma [Release](https://github.com/Dev-sLeo/agendar-entrega/releases/new) a partir dessa tag, descrevendo as mudanças — esse texto aparece no changelog exibido pelo WordPress. Sem uma Release, a atualização funciona do mesmo jeito, só não mostra changelog.
5. Em algumas horas (ou imediatamente ao clicar em "Verificar novamente" na tela de plugins), os sites com o plugin instalado vão detectar a atualização.

### Repositório privado

Se o repositório for privado, defina um [token de acesso pessoal](https://github.com/settings/tokens) do GitHub (permissão `repo`) no `wp-config.php` de cada site:

```php
define( 'AE_GITHUB_TOKEN', 'ghp_xxxxxxxxxxxxxxxxxxxx' );
```

## Estrutura do código

```
agendar-entregas.php                       Bootstrap do plugin
includes/
  class-ae-plugin.php                      Inicialização dos módulos
  class-ae-updater.php                     Configura o Plugin Update Checker (atualização via GitHub)
  libs/plugin-update-checker/              Biblioteca vendorizada YahnisElsts/plugin-update-checker
  class-ae-cpt-turno.php                   CPT interno "turno"
  class-ae-disponibilidade.php             Regra central de vagas/disponibilidade
  class-ae-agendamentos.php                Tabela de agendamentos (wp_ae_agendamentos)
  class-ae-dias-bloqueados.php             Bloqueio por data e por dia da semana
  class-ae-limite-excecao.php              Ajuste manual de limite por data
  checkout/                                Campos e assets do checkout
  order/                                   Hooks de reserva/confirmação de vaga
  import/                                  Importação de dias bloqueados (arquivo/Google Sheets)
  admin/                                   Telas administrativas
```

## Licença

Uso interno — Upsites.
