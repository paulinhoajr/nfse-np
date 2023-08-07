<?php

namespace Paulinhoajr\NfseNp;

class Nota {
    public function criarNfse($payment, $user, $ultimaNota) {
        echo 'Nota fiscal criada com sucesso.';
        echo 'Payment: '.$payment;
        echo 'User: '.$user;
        echo 'Ultima Nota: '.$ultimaNota;
    }
}