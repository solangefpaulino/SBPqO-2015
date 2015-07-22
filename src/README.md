src
===

Pasta códigos-fonte das *"tools"* dos softwares desenvolvidos para apoio (inclusive make-file, etc.) das propostas e dos resultados entregues.


## Pastas e arquivos
 * *asserts*: casos homologados, para verificar se alterações no software não geram efeito colateral.
 * *css*: arquivos CSS de uso comum (das propostas), fontes de uso comum, imagens de uso comum.
 * *xsl*: skin-templates (casca simples com placeholders) e XSLTs. 

## Rodando no terminal
Os exemplos a seguir devem ser executados a partir de `pdfGenerator/clientes/BOR/SBPqO_resumos`
* `php etc/php/main.php -h`  mostra como usar o comando.
* `php etc/php/main.php --relat --in=material/originais-UTF8/AO.html | more` relatório completo da avaliação de A-6.
* `php etc/php/main.php --relat3 --in=material/originais-UTF8/AO.html` relatório reduzido, só com os IDs de A-6.
* `php etc/php/main.php --relat2 --in=material/originais-UTF8/ | more` relatório completo da avaliação de todos da pasta.
* ...
* `php etc/php/main.php --finalhtml --in=material/originais-UTF8/AO.html` HTML final do AO.
* `php etc/php/main.php --finalhtml --in=material/originais-UTF8/ | more` HTML final de todos.


