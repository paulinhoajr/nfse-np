<?php
require __DIR__.'/vendor/autoload.php';

use Paulinhoajr\NfseNp\Nota;

$nfse = new Nota();
$nfse->criarNfse('nao sei oq eh pagamento', 'pejota', '323231');