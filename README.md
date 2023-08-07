 # Nota Fiscal De Serviço Eletrônica (NFSE)
Módulo gerador de NFSE.

## Instalação:
Executar o comando abaixo
```shell
composer require paulinhoajr/nfse
```

## Documentação:
Teste legal ↓

```php
use Paulinhoajr\NfseNp\Nota;

$nfse = new Nota();
$nfse->criarNfse('nao sei oq eh pagamento', 'pejota', '323231');
```

## Requisitos:
> Necessário PHP 7.0 ou superior.