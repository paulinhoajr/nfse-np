<?php

namespace App\Classes;

use App\PaymentItem;

class Notas
{
    public $priKEY = '';
    public $pubKEY = '';
    public $certKEY = '';
    public $pfxTimestamp = '';

    public function criarNfse($payment, $user, $ultimaNota)
    {
        //$NumeroNota = $ultimaNota->numero_nota + 1;
        $NumeroLote = $ultimaNota->numero_lote + 1;
        $NumeroRPS = $ultimaNota->numero_rps + 1;

        $date = date("Y-m-d")."T".date("H:i:s"); // data do envio
        $ano = date("Y"); // ano corrente do envio

        if (strlen($user->cpf_cnpj) > 11){
            $opcao = 'CNPJ';
        }else{
            $opcao = 'CPF';
        }

        $Cnpj = env('VOOPE_CNPJ');
        $InscricaoMunicipal = env('VOOPE_INSCRICAO_MUNICIPAL');
        $RazaoSocial = env('VOOPE_RAZAO_SOCIAL');
        $CodigoMunicipioEmpresa = env('VOOPE_CODIGO_MUNICIPIO');

        $Nome = $user->name;
        $Cnpjcpf = $user->cpf_cnpj;
        $Endereco = $user->address . ($user->complement!=""?" - ".$user->complement:'');
        $Numero = $user->number;
        $Bairro = $user->neighborhood;
        $Cepcliente = $user->cep;
        $Telefone = ($user->mobile!=""?$user->mobile:$user->phone);
        $Email    = $user->email;
        $UFCliente = $user->state->abbreviation;
        $CodigoMunicipioCliente = $user->ibge;
        $InscricaoMunicipalCliente = $user->im;
        $InscricaoEstadualCliente = $user->ie;

        $this->__loadCerts();

        $identificador = 1;
        $numerosequencial = str_pad($NumeroLote, 16, '0', STR_PAD_LEFT);
        $chave = $identificador . $ano . $numerosequencial;

        $identificadorprest = 1;
        $numerosequencialprest = str_pad($NumeroRPS, 16, '0', STR_PAD_LEFT);
        $chaveprestacao = $identificadorprest . $user->cpf_cnpj . $numerosequencialprest;

        $dom = new \DOMDocument("1.0", "utf-8");

        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;

        $root = $dom->createElement("EnviarLoteRpsSincronoEnvio");
        $root->setAttribute("xmlns", "http://www.abrasf.org.br/nfse.xsd");
        $LoteRps = $dom->createElement("LoteRps");
        $LoteRps->setAttribute("Id", 'L' . $chave);
        $LoteRps->setAttribute("versao", "20.01");
        $nrlote = $dom->createElement("NumeroLote", $NumeroLote);
        $CpfCnpjnovo = $dom->createElement("CpfCnpj");
        $PrestadorNovoCnpj = $dom->createElement("Cnpj", $Cnpj);
        $CpfCnpjnovo->appendChild($PrestadorNovoCnpj);
        $Inscricao = $dom->createElement("InscricaoMunicipal", $InscricaoMunicipal);
        $QuantidadeRps = $dom->createElement("QuantidadeRps", 1);
        $ListaRps = $dom->createElement("ListaRps");
        $Rps = $dom->createElement("Rps");
        $ListaRps->appendChild($Rps);
        $tcDeclaracaoPrestacaoServico = $dom->createElement("tcDeclaracaoPrestacaoServico");
        $Rps->appendChild($tcDeclaracaoPrestacaoServico);
        $InfDeclaracaoPrestacaoServico = $dom->createElement("InfDeclaracaoPrestacaoServico");
        $InfDeclaracaoPrestacaoServico->setAttribute("Id", $chaveprestacao);
        $tcDeclaracaoPrestacaoServico->appendChild($InfDeclaracaoPrestacaoServico);
        $Rps2 = $dom->createElement("Rps");
        $InfDeclaracaoPrestacaoServico->appendChild($Rps2);
        $IdentificacaoRps = $dom->createElement("IdentificacaoRps");
        $Rps2->appendChild($IdentificacaoRps);
        $NumeroRPSx = $dom->createElement("Numero", $NumeroRPS);
        $Seriex = $dom->createElement("Serie", "UNICA");
        $Tipox = $dom->createElement("Tipo", "1");
        $IdentificacaoRps->appendChild($NumeroRPSx);
        $IdentificacaoRps->appendChild($Seriex);
        $IdentificacaoRps->appendChild($Tipox);
        $data2 = $dom->createElement("DataEmissao", $date);
        $Rps2->appendChild($data2);
        $Statusx = $dom->createElement("Status", "1");
        $Rps2->appendChild($Statusx);
        $RpsSubstituido = $dom->createElement("RpsSubstituido");
        $Rps2->appendChild($RpsSubstituido);
        $Numerosubs = $dom->createElement("Numero");
        $RpsSubstituido->appendChild($Numerosubs);
        $Seriesubs = $dom->createElement("Serie");
        $RpsSubstituido->appendChild($Seriesubs);
        $Tiposubs = $dom->createElement("Tipo", "1");
        $RpsSubstituido->appendChild($Tiposubs);
        $SiglaUF = $dom->createElement("SiglaUF", "RS");
        $IdCidade = $dom->createElement("IdCidade", $CodigoMunicipioEmpresa);
        $Competencia = $dom->createElement("Competencia", $date);
        $Servico = $dom->createElement("Servico");
        $InfDeclaracaoPrestacaoServico->appendChild($SiglaUF);
        $InfDeclaracaoPrestacaoServico->appendChild($IdCidade);
        $InfDeclaracaoPrestacaoServico->appendChild($Competencia);
        $InfDeclaracaoPrestacaoServico->appendChild($Servico);

////////////////////////////////////////////////////////////////////////////////// loop

        foreach ($payment->itemsGroup as $item){ // agrupo itens igual 1.05 , 1.07

            $itemNews = PaymentItem::where('item', $item->item)->where('payment_id', $item->payment_id)->get();

            //dd($itemNews);

            $itemValor = 0;
            $itemItem = "";
            $itemIss = "";
            $itemCnae = "";
            $itemObs = "";//($user->comment_nota!=""?$user->comment_nota." - ":''); // tamanho do texto até de 2000 caracteres

            foreach ($itemNews as $inews)
            {
                $itemValor += $inews->valor;
                $itemObs .= $inews->obs." - ";
                $itemItem = $inews->item;
                $itemIss = $inews->iss;
                $itemIssTest[] = $inews->iss;
                $itemCnae = $inews->cnae;
            }

            //dd($itemIssTest, $itemIss);
            if ($user->iss != null){
                $itemIss = $user->iss;
            }

            if ($user->comment_nota != null){
                $itemObs = $user->comment_nota;
            }

            if ($payment->descricao != ""){
                $itemObs = $itemObs ." - ". $payment->descricao;
            }

            if ($payment->tipo==1){
                if ($payment->descricao!=null && $payment->descricao!=""){
                    $itemObs = $payment->descricao;
                }
            }

            $AliquotaPorc = ($itemIss/100); // 0.03 ou 0.035

            $ValorIs = $itemValor * $AliquotaPorc;
            $ValorIss1 = number_format($ValorIs, 2, '.', '');

            $ValorDeducoes1 = "0.00";
            $ValorPis1 = "0.00";
            $ValorCofins1 = "0.00";
            $ValorInss1 = "0.00";
            $ValorIr1 = "0.00";
            $ValorCsll1 = "0.00";
            $OutrasRetencoes1 = "0.00";
            $DescontoIncondicionado1 = "0.00";
            $DescontoCondicionado1 = "0.00";
            $IrrfIndenizacao1 = "0.00";

            $tcDadosServico = $dom->createElement("tcDadosServico");
            $Servico->appendChild($tcDadosServico);
            $Valores = $dom->createElement("Valores");
            $tcDadosServico->appendChild($Valores);
            $ValorServicos = $dom->createElement("ValorServicos", $itemValor);
            $ValorDeducoes = $dom->createElement("ValorDeducoes", $ValorDeducoes1);
            $ValorPis = $dom->createElement("ValorPis", $ValorPis1);
            $ValorCofins = $dom->createElement("ValorCofins", $ValorCofins1);
            $ValorInss = $dom->createElement("ValorInss", $ValorInss1);
            $ValorIr = $dom->createElement("ValorIr", $ValorIr1);
            $ValorCsll = $dom->createElement("ValorCsll", $ValorCsll1);
            $OutrasRetencoes = $dom->createElement("OutrasRetencoes", $OutrasRetencoes1);
            $ValorIss = $dom->createElement("ValorIss", $ValorIss1);
            $Aliquota1 = $dom->createElement("Aliquota", $itemIss);
            $DescontoIncondicionado = $dom->createElement("DescontoIncondicionado", $DescontoIncondicionado1);
            $DescontoCondicionado = $dom->createElement("DescontoCondicionado", $DescontoCondicionado1);
            $IrrfIndenizacao = $dom->createElement("IrrfIndenizacao", $IrrfIndenizacao1);
            $Valores->appendChild($ValorServicos);
            $Valores->appendChild($ValorDeducoes);
            $Valores->appendChild($ValorPis);
            $Valores->appendChild($ValorCofins);
            $Valores->appendChild($ValorInss);
            $Valores->appendChild($ValorIr);
            $Valores->appendChild($ValorCsll);
            $Valores->appendChild($OutrasRetencoes);
            $Valores->appendChild($ValorIss);
            $Valores->appendChild($Aliquota1);
            $Valores->appendChild($DescontoIncondicionado);
            $Valores->appendChild($DescontoCondicionado);
            $Valores->appendChild($IrrfIndenizacao);
            //$IssRetidovar = $itemRetido; // 2 para não e 1 para sim
            $IssRetido = $dom->createElement("IssRetido", $user->nota_retida);
            $ResponsavelRetencaovar = ($user->nota_retida==2?'1':'2'); // '1'; // 1 se issretidor for 2, senao 2 para tomador ou 3 para intermediário
            $ResponsavelRetencao = $dom->createElement("ResponsavelRetencao", $ResponsavelRetencaovar);
            //dd($IssRetido, $ResponsavelRetencaovar, $ResponsavelRetencao);
            $ItemListaServico = $dom->createElement("ItemListaServico", $itemItem);
            $CodidoCnae1 = $dom->createElement("CodidoCnae", $itemCnae);
            $CodigoTributacaoMunicipio = $dom->createElement("CodigoTributacaoMunicipio", "0");
            $Discriminacao = $dom->createElement("Discriminacao", $itemObs);
            $CodigoMunicipioNovo = $dom->createElement("CodigoMunicipio", $CodigoMunicipioEmpresa);
            $CodigoPaisNovo = $dom->createElement("CodigoPais", "1058");
            $ExigibilidadeISS = $dom->createElement("ExigibilidadeISS", "1");
            $MunicipioIncidencia = $dom->createElement("MunicipioIncidencia", $CodigoMunicipioEmpresa);

            $tcDadosServico->appendChild($IssRetido);
            $tcDadosServico->appendChild($ResponsavelRetencao);
            $tcDadosServico->appendChild($ItemListaServico);
            $tcDadosServico->appendChild($CodidoCnae1);
            $tcDadosServico->appendChild($CodigoTributacaoMunicipio);
            $tcDadosServico->appendChild($Discriminacao);
            $tcDadosServico->appendChild($CodigoMunicipioNovo);
            $tcDadosServico->appendChild($CodigoPaisNovo);
            $tcDadosServico->appendChild($ExigibilidadeISS);
            $tcDadosServico->appendChild($MunicipioIncidencia);

        }

        //dd($user->nota_retida, $IssRetido, $ResponsavelRetencaovar, $ResponsavelRetencao);
////////////////////////////////////// fim do loop

        $Prestador = $dom->createElement("Prestador");
        $InfDeclaracaoPrestacaoServico->appendChild($Prestador);
        $Cnpjpres = $dom->createElement("CpfCnpj");
        $Prestador->appendChild($Cnpjpres);
        $PrestadorCnpj2 = $dom->createElement("Cnpj", $Cnpj);
        $Cnpjpres->appendChild($PrestadorCnpj2);
        $RazaoSocialPrestador = $dom->createElement("RazaoSocial", $RazaoSocial);
        $Prestador->appendChild($RazaoSocialPrestador);
        $InscricaoMunicipalpres = $dom->createElement("InscricaoMunicipal", $InscricaoMunicipal);
        $Prestador->appendChild($InscricaoMunicipalpres);

        $Tomador = $dom->createElement("Tomador");
        $InfDeclaracaoPrestacaoServico->appendChild($Tomador);
        $IdentificacaoTomador = $dom->createElement("IdentificacaoTomador");
        $Tomador->appendChild($IdentificacaoTomador);
        $CpfCnpjtomador = $dom->createElement("CpfCnpj");
        $IdentificacaoTomador->appendChild($CpfCnpjtomador);
        $TomadorCpf = $dom->createElement("Cpf", $Cnpjcpf);
        $TomadorCnpj = $dom->createElement("Cnpj", $Cnpjcpf);
        if ($opcao == 'CPF') {
            $CpfCnpjtomador->appendChild($TomadorCpf);
            $InscricaoMunicipalTomador = $dom->createElement("InscricaoMunicipal");
            if ($InscricaoEstadualCliente!=""){
                $InscricaoEstadualTomador = $dom->createElement("InscricaoEstadual", $InscricaoEstadualCliente);
            }else{
                $InscricaoEstadualTomador = $dom->createElement("InscricaoEstadual");
            }

        } else {
            $CpfCnpjtomador->appendChild($TomadorCnpj);
            $InscricaoMunicipalTomador = $dom->createElement("InscricaoMunicipal", $InscricaoMunicipalCliente);
            $InscricaoEstadualTomador = $dom->createElement("InscricaoEstadual", $InscricaoEstadualCliente);
        }

        $IdentificacaoTomador->appendChild($InscricaoMunicipalTomador);
        $IdentificacaoTomador->appendChild($InscricaoEstadualTomador);
        $RazaoSocialTomador = $dom->createElement("RazaoSocial", $Nome);
        $Tomador->appendChild($RazaoSocialTomador);

        $EnderecoTomador = $dom->createElement("Endereco");
        $EEnderecoTomador = $dom->createElement("Endereco", $Endereco);
        $NumeroTomador = $dom->createElement("Numero", $Numero);
        $ComplementoTomador = $dom->createElement("Complemento");
        $BairroTomador = $dom->createElement("Bairro", $Bairro);

        $CodigoMunicipioTomador = $dom->createElement("CodigoMunicipio", $CodigoMunicipioCliente);

        $UFTomador = $dom->createElement("Uf", $UFCliente);
        $CodigoPaisTomador = $dom->createElement("CodigoPais", "1058");
        $CepTomador = $dom->createElement("Cep", $Cepcliente);
        $ContatoTomador = $dom->createElement("Contato");
        if ($Telefone != '') {
            $TelefoneTomador = $dom->createElement("Telefone", $Telefone);
        } else {
            $TelefoneTomador = $dom->createElement("Telefone");
        }

        if ($Email != '') {
            $EmailTomador = $dom->createElement("Email", $Email);
        } else {
            $EmailTomador = $dom->createElement("Email");
        }

        $Tomador->appendChild($EnderecoTomador);
        $EnderecoTomador->appendChild($EEnderecoTomador);
        $EnderecoTomador->appendChild($NumeroTomador);
        $EnderecoTomador->appendChild($ComplementoTomador);
        $EnderecoTomador->appendChild($BairroTomador);
        $EnderecoTomador->appendChild($CodigoMunicipioTomador);
        $EnderecoTomador->appendChild($UFTomador);
        $EnderecoTomador->appendChild($CodigoPaisTomador);
        $EnderecoTomador->appendChild($CepTomador);
        $Tomador->appendChild($ContatoTomador);
        $ContatoTomador->appendChild($TelefoneTomador);
        $ContatoTomador->appendChild($EmailTomador);

        $Intermediario = $dom->createElement("Intermediario");
        $IdentificacaoIntermediario = $dom->createElement("IdentificacaoIntermediario");
        $CpfCnpjInt = $dom->createElement("CpfCnpj");
        $CpfInt = $dom->createElement("Cpf");
        $InscricaoMunicipalInt = $dom->createElement("InscricaoMunicipal");
        $RazaoSocialInt = $dom->createElement("RazaoSocial");

        $InfDeclaracaoPrestacaoServico->appendChild($Intermediario);
        $Intermediario->appendChild($IdentificacaoIntermediario);
        $IdentificacaoIntermediario->appendChild($CpfCnpjInt);
        $CpfCnpjInt->appendChild($CpfInt);
        $IdentificacaoIntermediario->appendChild($InscricaoMunicipalInt);
        $Intermediario->appendChild($RazaoSocialInt);

        $Construcaocivil = $dom->createElement("ConstrucaoCivil");
        $CodigoObra = $dom->createElement("CodigoObra");
        $Art = $dom->createElement("Art");
        $InfDeclaracaoPrestacaoServico->appendChild($Construcaocivil);
        $Construcaocivil->appendChild($CodigoObra);
        $Construcaocivil->appendChild($Art);

        $RegimeEspecialTributacao = $dom->createElement("RegimeEspecialTributacao", "6");
        $NaturezaOperacao = $dom->createElement("NaturezaOperacao", "1");
        $OptanteSimplesNacional = $dom->createElement("OptanteSimplesNacional", "2");
        $IncentivoFiscal = $dom->createElement("IncentivoFiscal", "2");
        $PercentualCargaTributaria = $dom->createElement("PercentualCargaTributaria", "3");
        $ValorCargaTributaria = $dom->createElement("ValorCargaTributaria", "30");
        $PercentualCargaTributariaEstadual = $dom->createElement("PercentualCargaTributariaEstadual", "3");
        $ValorCargaTributariaEstadual = $dom->createElement("ValorCargaTributariaEstadual", "30");
        $PercentualCargaTributariaMunicipal = $dom->createElement("PercentualCargaTributariaMunicipal", "3");
        $ValorCargaTributariaMunicipal = $dom->createElement("ValorCargaTributariaMunicipal", "30");
        $SiglaUF = $dom->createElement("SiglaUF", "RS");
        $IdCidade = $dom->createElement("IdCidade", $CodigoMunicipioEmpresa);
        $NumeroParcelas = $dom->createElement("NumeroParcelas", "0");
        $InfDeclaracaoPrestacaoServico->appendChild($RegimeEspecialTributacao);
        $InfDeclaracaoPrestacaoServico->appendChild($NaturezaOperacao);
        $InfDeclaracaoPrestacaoServico->appendChild($OptanteSimplesNacional);
        $InfDeclaracaoPrestacaoServico->appendChild($IncentivoFiscal);
        $InfDeclaracaoPrestacaoServico->appendChild($PercentualCargaTributaria);
        $InfDeclaracaoPrestacaoServico->appendChild($ValorCargaTributaria);
        $InfDeclaracaoPrestacaoServico->appendChild($PercentualCargaTributariaEstadual);
        $InfDeclaracaoPrestacaoServico->appendChild($ValorCargaTributariaEstadual);
        $InfDeclaracaoPrestacaoServico->appendChild($PercentualCargaTributariaMunicipal);
        $InfDeclaracaoPrestacaoServico->appendChild($ValorCargaTributariaMunicipal);
        $InfDeclaracaoPrestacaoServico->appendChild($SiglaUF);
        $InfDeclaracaoPrestacaoServico->appendChild($IdCidade);
        $InfDeclaracaoPrestacaoServico->appendChild($NumeroParcelas);

        $LoteRps->appendChild($nrlote);
        $LoteRps->appendChild($CpfCnpjnovo);
        $LoteRps->appendChild($Inscricao);
        $LoteRps->appendChild($QuantidadeRps);
        $LoteRps->appendChild($ListaRps);
        $root->appendChild($LoteRps);
        $dom->appendChild($root);

        $xml = $dom->saveXML();
        $xml = str_replace('<?xml version="1.0" encoding="utf-8"?>', '<?xml version="1.0" encoding="utf-8" standalone="no"?>', $xml);
        $xml = str_replace('<?xml version="1.0" encoding="utf-8" standalone="no"?>', '', $xml);
        $xml = str_replace('<?xml version="1.0" encoding="utf-8"?>', '', $xml);
        $xml = str_replace("\n", "", $xml);
        $xml = str_replace("  ", " ", $xml);
        $xml = str_replace("  ", " ", $xml);
        $xml = str_replace("  ", " ", $xml);
        $xml = str_replace("  ", " ", $xml);
        $xml = str_replace("  ", " ", $xml);
        $xml = str_replace("> <", "><", $xml);
        $tag = 'InfDeclaracaoPrestacaoServico';
        $xml = html_entity_decode(stripslashes($xml), ENT_QUOTES, 'UTF-8');

        $stringXML = $this->signXML($xml, $tag);

        return $this->sendSoapMessage($stringXML);
    }

    public function __loadCerts(){
        if (!function_exists('openssl_pkcs12_read')) {
            dd("Erro no carregamento dos certificados", "Função não existente: openssl_pkcs12_read!!");
        }

        //monta o path completo com o nome da chave privada
        $this->priKEY = public_path() . '/priKEY.pem';

        //monta o path completo com o nome da chave prublica
        $this->pubKEY = public_path() . '/pubKEY.pem';

        //monta o path completo com o nome do certificado (chave publica e privada) em formato pem
        $this->certKEY = public_path() . '/certKEY.pem';

        //monta o caminho completo ate o certificado pfx
        $pfxCert = public_path()."/". env('VOOPE_CERTIFICADO_NAME');

        //verifica se o arquivo existe
        if (!file_exists($pfxCert)) {
            dd("Arquivo do Certificado não encontrado => " . $pfxCert);
        }

        //carrega o certificado em um string
        $pfxContent = file_get_contents($pfxCert);

        //carrega os certificados e chaves para um array denominado $x509certdata
        if (!openssl_pkcs12_read($pfxContent, $x509certdata, env('VOOPE_CERTIFICADO_PASS'))) {
            dd("O certificado não pode ser lido. Pode estar corrompido ou a senha cadastrada está errada!");
        }

        //Verifica se o certificado é válido

        try {
            $this->__validCerts($x509certdata['cert']);
        } catch (\Exception $e) {
            dd($e->getMessage());
        }

        //aqui verifica se existem as chaves em formato PEM
        //se existirem pega a data da validade dos arquivos PEM
        //e compara com a data de validade do PFX
        //caso a data de validade do PFX for maior que a data do PEM
        //deleta dos arquivos PEM, recria e prossegue

        $flagNovo = false;

        if (file_exists($this->pubKEY)) {

            $cert = file_get_contents($this->pubKEY);

            if (!$data = openssl_x509_read($cert)) {
                //arquivo não pode ser lido como um certificado
                //entao deletar
                $flagNovo = true;
            } else {
                //pegar a data de validade do mesmo
                $cert_data = openssl_x509_parse($data);

                // reformata a data de validade;
                $ano = substr($cert_data['validTo'], 0, 2);
                $mes = substr($cert_data['validTo'], 2, 2);
                $dia = substr($cert_data['validTo'], 4, 2);

                //obtem o timeestamp da data de validade do certificado
                $dValPubKey = gmmktime(0, 0, 0, $mes, $dia, $ano);

                //var_dump(date('d/m/Y',$dValPubKey));exit();
                //compara esse timestamp com o do pfx que foi carregado

                if ($dValPubKey < $this->pfxTimestamp) {
                    //o arquivo PEM eh de um certificado anterior
                    //entao apagar os arquivos PEM
                    $flagNovo = true;
                }
            }
        } else {
            //arquivo não localizado
            $flagNovo = true;
        }

        //verificar a chave privada em PEM
        if (!file_exists($this->priKEY)) {
            //arquivo nao encontrado
            $flagNovo = true;
        }

        //verificar o certificado em PEM
        if (!file_exists($this->certKEY)) {
            //arquivo não encontrado
            $flagNovo = true;
        }

        //criar novos arquivos PEM
        if ($flagNovo) {
            if (file_exists($this->pubKEY)) {
                unlink($this->pubKEY);
            }
            if (file_exists($this->priKEY)) {
                unlink($this->priKEY);
            }
            if (file_exists($this->certKEY)) {
                unlink($this->certKEY);
            }

            //recriar os arquivos pem com o arquivo pfx
            /*if (!file_put_contents($this->priKEY, $x509certdata['pkey'])) {
                dd("Impossivel gravar no diretório! Permissão negada!");
            }*/
            if (!file_put_contents($this->priKEY,$x509certdata['pkey'])) {
                //echo "Impossivel gravar no diretório! Permissão negada!";
                throw new nfsephpException("Impossivel gravar no diretório! Permissão negada!",self::STOP_CRITICAL);
                return false;
                dd("Impossivel gravar no diretório! Permissão negada!");
            }

            file_put_contents($this->pubKEY, $x509certdata['cert']);
            file_put_contents($this->certKEY, $x509certdata['pkey']."\r\n".$x509certdata['cert']);

        }

        return true;
    }

    public function sendSoapMessage($stringXML)
    {
        $soap_msg = '<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
<soap:Body>
<mEnvioLoteRPSSincrono xmlns="http://tempuri.org/">
<remessa>
<![CDATA[' . $stringXML . ']]>
</remessa>
</mEnvioLoteRPSSincrono>
</soap:Body>
</soap:Envelope>';

        $soap_msg = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '<?xml version="1.0" encoding="UTF-8" standalone="no"?>', $soap_msg);
        $soap_msg = str_replace('<?xml version="1.0" encoding="UTF-8" standalone="no"?>', '', $soap_msg);
        $soap_msg = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $soap_msg);
        $soap_msg = str_replace("\n", "", $soap_msg);
        $soap_msg = str_replace("  ", " ", $soap_msg);
        $soap_msg = str_replace("> <", "><", $soap_msg);

        $headers = array(
            "Content-type: text/xml; charset=\"utf-8\"",
            "Accept: text/xml",
            "Cache-Control: no-cache",
            "Pragma: no-cache",
            "SOAPAction: http://tempuri.org/mEnvioLoteRPSSincrono",
            "Content-length: " . strlen($soap_msg),
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_URL, (env('NOTA_PRODUCAO')==1?env('NOTA_URL_ENVIO'):env('HNOTA_URL_ENVIO')));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 86400);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $soap_msg);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($ch);
        //dd($response);
        return $response;
    }

    protected function __validCerts($cert = '')
    {
        if ($cert == '') {
            dd("O certificado é um parâmetro obrigatorio");
        }

        if (!$data = openssl_x509_read($cert)) {
            dd("O certificado não pode ser lido pelo SSL - $cert .");
        }

        $cert_data = openssl_x509_parse($data);

        // reformata a data de validade;
        $ano = substr($cert_data['validTo'], 0, 2);
        $mes = substr($cert_data['validTo'], 2, 2);
        $dia = substr($cert_data['validTo'], 4, 2);

        //obtem o timestamp da data de validade do certificado
        $dValid = gmmktime(0, 0, 0, $mes, $dia, $ano);

        // obtem o timestamp da data de hoje
        $dHoje = gmmktime(0, 0, 0, date("m"), date("d"), date("Y"));

        // compara a data de validade com a data atual
        if ($dValid < $dHoje) {
            dd("A Validade do certificado expirou em " . $dia . '/' . $mes . '/' . $ano);
        }

        $this->pfxTimestamp = $dValid;

        return true;
    }

    public function consultalote($protocolo)
    {
        $xml = '<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
<soap:Body>
<mConsultaLoteRPS xmlns="http://tempuri.org/">
<remessa>
<![CDATA[<ConsultarLoteRpsEnvio xmlns="http://www.abrasf.org.br/nfse.xsd" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><Prestador><CpfCnpj><Cnpj>' . env('VOOPE_CNPJ') . '</Cnpj></CpfCnpj><RazaoSocial>' . env('VOOPE_RAZAO_SOCIAL') . '</RazaoSocial><InscricaoMunicipal>' . env('VOOPE_INSCRICAO_MUNICIPAL') . '</InscricaoMunicipal></Prestador><Protocolo>' . $protocolo . '</Protocolo></ConsultarLoteRpsEnvio>]]></remessa>
<cabecalho>
<![CDATA[<cabecalho xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns="http://www.abrasf.org.br/nfse.xsd" <versaoDados>20.01</versaoDados> </cabecalho>]]></cabecalho>
</mConsultaLoteRPS>
</soap:Body>
</soap:Envelope>';

        $soap_msg = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '<?xml version="1.0" encoding="UTF-8" standalone="no"?>', $xml);
        $soap_msg = str_replace('<?xml version="1.0" encoding="UTF-8" standalone="no"?>', '', $soap_msg);
        $soap_msg = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $soap_msg);
        $soap_msg = str_replace("\n", "", $soap_msg);
        $soap_msg = str_replace("  ", " ", $soap_msg);
        $soap_msg = str_replace("> <", "><", $soap_msg);

        $headers = array(
            "Content-type: text/xml; charset=\"utf-8\"",
            "Accept: text/xml",
            "Cache-Control: no-cache",
            "Pragma: no-cache",
            "SOAPAction: http://tempuri.org/mConsultaLoteRPS",
            "Content-length: " . strlen($soap_msg),
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_URL, (env('NOTA_PRODUCAO')==1?env('NOTA_URL_CONSULTA_LOTE'):env('HNOTA_URL_CONSULTA_LOTE')));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 86400);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $soap_msg);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $retorno = curl_exec($ch);

//pega os dados do array retornado pelo NuSoap
        $retorno = str_replace('&lt;', '<', $retorno);
        $retorno = str_replace('&gt;', '>', $retorno);
        $retorno = str_replace('<?xml version="1.0" encoding="utf-8"?>', '', $retorno);
        $xmlresp = utf8_encode($retorno);
        if ($xmlresp == '') {
            echo 'erro';
        }
//tratar dados de retorno
        $doc = new \DOMDocument(); //cria objeto DOM
        $doc->formatOutput = FALSE;
        $doc->preserveWhiteSpace = FALSE;
        $doc->loadXML($retorno, LIBXML_NOBLANKS | LIBXML_NOEMPTYTAG);

// status do recebimento ou mensagem de erro
        $aRet['Situacao'] = $doc->getElementsByTagName('Situacao')->item(0)->nodeValue;
        $aRet['Numero'] = $doc->getElementsByTagName('Numero')->item(0)->nodeValue;
        $aRet['CodigoVerificacao'] = $doc->getElementsByTagName('CodigoVerificacao')->item(0)->nodeValue;
        $aRet['DataEmissao'] = $doc->getElementsByTagName('DataEmissao')->item(0)->nodeValue;
        $aRet['LinkNota'] = $doc->getElementsByTagName('LinkNota')->item(0)->nodeValue;
        return $aRet;

    }

    public function cancelarNfse($NumeroNota)
    {
        // pega dados certificado é obrigatorio para assinar nota
        $this->__loadCerts();

        $identificador = 2;
        $NumeroNotaposicao = str_pad($NumeroNota, 16, '0', STR_PAD_LEFT);
        $chavenota = $identificador . env('VOOPE_CNPJ') . $NumeroNotaposicao;
        $tag = 'InfPedidoCancelamento';

        $dom = new \DOMDocument("1.0", "utf-8");
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $root = $dom->createElement("CancelarNfseEnvio");
        $root->setAttribute("xmlns", "http://www.abrasf.org.br/nfse.xsd");
        $root->setAttribute("xmlns:xsi", "http://www.w3.org/2001/XMLSchema-instance");
        $root->setAttribute("xmlns:xsd", "http://www.w3.org/2001/XMLSchema");

        $Pedido = $dom->createElement("Pedido");
        $InfPedidoCancelamento = $dom->createElement("InfPedidoCancelamento");
        $InfPedidoCancelamento->setAttribute("Id", $chavenota);
        $Pedido->appendChild($InfPedidoCancelamento);
        $IdentificacaoNfse = $dom->createElement("IdentificacaoNfse");
        $InfPedidoCancelamento->appendChild($IdentificacaoNfse);
        $numero = $dom->createElement("Numero", $NumeroNota);
        $CpfCnpjNovo = $dom->createElement("CpfCnpj");
        $PrestadorCnpj = $dom->createElement("Cnpj", env('VOOPE_CNPJ'));
        $CpfCnpjNovo->appendChild($PrestadorCnpj);
        $InscricaoM = $dom->createElement("InscricaoMunicipal", env('VOOPE_INSCRICAO_MUNICIPAL'));
        $CodigoMunicipio = $dom->createElement("CodigoMunicipio", env('VOOPE_CODIGO_MUNICIPIO'));
        $CodigoCancelamento = $dom->createElement("CodigoCancelamento", "1");

        $IdentificacaoNfse->appendChild($numero);
        $IdentificacaoNfse->appendChild($CpfCnpjNovo);
        $IdentificacaoNfse->appendChild($InscricaoM);
        $IdentificacaoNfse->appendChild($CodigoMunicipio);
        $InfPedidoCancelamento->appendChild($CodigoCancelamento);
        $root->appendChild($Pedido);
        $dom->appendChild($root);
        //header("Content-Type: text/xml");
        $xml1 = $dom->saveXML();

        $xml1 = str_replace('<?xml version="1.0" encoding="utf-8"?>', '<?xml version="1.0" encoding="utf-8" standalone="no"?>', $xml1);
        $xml1 = str_replace('<?xml version="1.0" encoding="utf-8" standalone="no"?>', '', $xml1);
        $xml1 = str_replace('<?xml version="1.0" encoding="utf-8"?>', '', $xml1);
        $xml1 = str_replace("\n", "", $xml1);
        $xml1 = str_replace("  ", " ", $xml1);
        $xml1 = str_replace("  ", " ", $xml1);
        $xml1 = str_replace("  ", " ", $xml1);
        $xml1 = str_replace("  ", " ", $xml1);
        $xml1 = str_replace("  ", " ", $xml1);
        $xml1 = str_replace("> <", "><", $xml1);
        $assinaxml1 = $this->signXML($xml1, $tag);
        $stringXML = $assinaxml1;

        $soap_msg = '<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
<soap:Body>
<mCancelamentoNFSe xmlns="http://tempuri.org/">
<remessa>
<![CDATA[' . $stringXML . ']]>
</remessa>
<cabecalho>
<![CDATA[<cabecalho xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns="http://www.abrasf.org.br/nfse.xsd" <versaoDados>20.01</versaoDados> </cabecalho>]]>
</cabecalho>
</mCancelamentoNFSe>
</soap:Body>
</soap:Envelope>';

        $soap_msg = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '<?xml version="1.0" encoding="UTF-8" standalone="no"?>', $soap_msg);
        $soap_msg = str_replace('<?xml version="1.0" encoding="UTF-8" standalone="no"?>', '', $soap_msg);
        $soap_msg = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $soap_msg);
        $soap_msg = str_replace("\n", "", $soap_msg);
        $soap_msg = str_replace("  ", " ", $soap_msg);
        $soap_msg = str_replace("> <", "><", $soap_msg);

        $headers = array(
            "Content-type: text/xml;charset=\"utf-8\"",
            "Accept: text/xml",
            "Cache-Control: no-cache",
            "Pragma: no-cache",
            "SOAPAction: http://tempuri.org/mCancelamentoNFSe",
            "Content-length: " . strlen($soap_msg),
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_URL, (env('NOTA_PRODUCAO')==1?env('NOTA_URL_CANCELAR'):env('HNOTA_URL_CANCELAR')));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $soap_msg);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $retorno = curl_exec($ch);

        $retorno=str_replace('&lt;','<',$retorno);
        $retorno=str_replace('&gt;','>',$retorno);
        $retorno=str_replace('<?xml version="1.0" encoding="utf-8"?>','',$retorno);
        //$xmlresp = utf8_encode($retorno);

        if ($retorno!=""){
            //tratar dados de retorno
            $doc = new \DOMDocument(); //cria objeto DOM
            $doc->formatOutput = FALSE;
            $doc->preserveWhiteSpace = FALSE;
            $doc->loadXML($retorno, LIBXML_NOBLANKS | LIBXML_NOEMPTYTAG);

            $MensagemRetorno = $doc->getElementsByTagName('MensagemRetorno')->item(0)->nodeValue;

            if($MensagemRetorno != ""){
                //$Codigo = $doc->getElementsByTagName('Codigo')->item(0)->nodeValue;
                $Mensagem = $doc->getElementsByTagName('Mensagem')->item(0)->nodeValue;
                $Correcao = $doc->getElementsByTagName('Correcao')->item(0)->nodeValue;
                $data = [
                    'mensagem' => $Mensagem,
                    'correcao' => $Correcao,
                ];
            }else{
                $data = [
                    'mensagem' => null,
                    'correcao' => null,
                ];
            }
        }else{
            $data = [
                'mensagem' => null,
                'correcao' => null,
            ];
        }

        return $data;

    }

    public function ConsultarSequenciaLoteNotaRPSEnvio($Cnpj, $RazaoSocial, $InscricaoMunicipal)
    {
        $xml = '<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
<soap:Body>
<mConsultaSequenciaLoteNotaRPS xmlns="http://tempuri.org/">
<remessa>
<![CDATA[<ConsultarSequenciaLoteNotaRPSEnvio xmlns="http://www.abrasf.org.br/nfse.xsd">
<Prestador>
<CpfCnpj>
<Cnpj>' . $Cnpj . '</Cnpj>
</CpfCnpj>
<RazaoSocial>' . $RazaoSocial . '</RazaoSocial>
<InscricaoMunicipal>' . $InscricaoMunicipal . '</InscricaoMunicipal>
</Prestador>
</ConsultarSequenciaLoteNotaRPSEnvio>]]>
</remessa>
</mConsultaSequenciaLoteNotaRPS>
</soap:Body>
</soap:Envelope>';

        $headers = array(
            "Content-type: text/xml; charset=\"utf-8\"",
            "Accept: text/xml",
            "Cache-Control: no-cache",
            "Pragma: no-cache",
            "SOAPAction: http://tempuri.org/mConsultaSequenciaLoteNotaRPS",
            "Content-length: " . strlen($xml),
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_URL, (env('NOTA_PRODUCAO')==1?env('NOTA_URL_CONSULTA'):env('HNOTA_URL_CONSULTA')));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($ch);
        curl_close($ch);
        $x = json_encode($response);
        $array = explode('\r\n', $x);
        $arrayfinal = preg_replace("/[^0-9]/", "", $array);
        return $arrayfinal;
    }

    public function signXML($sXML, $tagid)
    {
        $fp = fopen($this->priKEY, "r");
        $priv_key = fread($fp, 8192);
        fclose($fp);
        $pkeyid = openssl_get_privatekey($priv_key);

        $order = array("\r\n", "\n", "\r", "\t");
        $replace = '';
        $sXML = str_replace($order, $replace, $sXML);
        $sXML = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '<?xml version="1.0" encoding="UTF-8" standalone="no"?>', $sXML);
        $sXML = str_replace('<?xml version="1.0" encoding="UTF-8" standalone="no"?>', '', $sXML);
        $sXML = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $sXML);
        $sXML = str_replace('<?xml version="1.0"?>', '', $sXML);
        $sXML = str_replace("\n", "", $sXML);
        $sXML = str_replace("  ", " ", $sXML);
        $sXML = str_replace("> <", "><", $sXML);

        $dom = new \DOMDocument('1.0', 'utf-8');
        $dom->preservWhiteSpace = false; //elimina espaços em branco
        $dom->formatOutput = false;
        $dom->loadXML($sXML, LIBXML_NOBLANKS | LIBXML_NOEMPTYTAG);

        $root = $dom->documentElement;
        $node = $dom->getElementsByTagName($tagid)->item(0);
        $Id = trim($node->getAttribute("Id"));
        $idnome = preg_replace('/[^0-9]/', '', $Id);

        $dados = $node->C14N(FALSE, FALSE, NULL, NULL);
        $dados = str_replace(' >', '>', $dados);
        $hashValue = hash('sha1', $dados, TRUE);
        $digValue = base64_encode($hashValue);

        $Signature = $dom->createElementNS('http://www.w3.org/2000/09/xmldsig#', 'Signature');
        $root->appendChild($Signature);
        $SignedInfo = $dom->createElement('SignedInfo');
        $Signature->appendChild($SignedInfo);

//Cannocalization
        $newNode = $dom->createElement('CanonicalizationMethod');
        $SignedInfo->appendChild($newNode);
        $newNode->setAttribute('Algorithm', 'http://www.w3.org/TR/2001/REC-xml-c14n-20010315');

//SignatureMethod
        $newNode1 = $dom->createElement('SignatureMethod');
        $SignedInfo->appendChild($newNode1);
        $newNode1->setAttribute('Algorithm', 'http://www.w3.org/2000/09/xmldsig#rsa-sha1');

//Reference
        $Reference = $dom->createElement('Reference');
        $SignedInfo->appendChild($Reference);
        $Reference->setAttribute('URI', '#' . $Id);

//Transforms
        $Transforms = $dom->createElement('Transforms');
        $Reference->appendChild($Transforms);

//Transform
        $newNode2 = $dom->createElement('Transform');
        $Transforms->appendChild($newNode2);
        $newNode2->setAttribute('Algorithm', 'http://www.w3.org/2000/09/xmldsig#enveloped-signature');

//Transform
        $newNode3 = $dom->createElement('Transform');
        $Transforms->appendChild($newNode3);
        $newNode3->setAttribute('Algorithm', 'http://www.w3.org/TR/2001/REC-xml-c14n-20010315');

//DigestMethod
        $newNode4 = $dom->createElement('DigestMethod');
        $Reference->appendChild($newNode4);
        $newNode4->setAttribute('Algorithm', 'http://www.w3.org/2000/09/xmldsig#sha1');

//DigestValue
        $newNode5 = $dom->createElement('DigestValue', $digValue);
        $Reference->appendChild($newNode5);

// extrai os dados a serem assinados para uma string
        $dadosn = $SignedInfo->C14N(false, false, null, null);

//inicializa a variavel que vai receber a assinatura
        $signaturevar = '';

        $resp = openssl_sign($dadosn, $signaturevar, $pkeyid);

//codifica assinatura para o padrao base64
        $signatureValueN = base64_encode($signaturevar);

//SignatureValue
        $newNodeSignature = $dom->createElement('SignatureValue', $signatureValueN);
        $Signature->appendChild($newNodeSignature);

//KeyInfo
        $KeyInfo = $dom->createElement('KeyInfo');
        $Signature->appendChild($KeyInfo);

//X509Data
        $X509Data = $dom->createElement('X509Data');
        $KeyInfo->appendChild($X509Data);

//X509Certificate
        //dd($this->pubKEY);
        $cert = $this->__cleanCerts($this->pubKEY);
        $newNode = $dom->createElement('X509Certificate', $cert);
        $X509Data->appendChild($newNode);

//grava na string o objeto DOM
        $returnxml = $dom->saveXML();

        openssl_free_key($pkeyid);

        $returnxml = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '<?xml version="1.0" encoding="UTF-8" standalone="no"?>', $returnxml);
        $returnxml = str_replace('<?xml version="1.0" encoding="UTF-8" standalone="no"?>', '', $returnxml);
        $returnxml = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $returnxml);
        $returnxml = str_replace('<?xml version="1.0"?>', '', $returnxml);
        $returnxml = str_replace("\n", "", $returnxml);
        $returnxml = str_replace("  ", " ", $returnxml);
        $returnxml = str_replace("> <", "><", $returnxml);

        //retorna o documento assinado
        return $returnxml;
    }

    protected function __cleanCerts($certFile)
    {
        try {
            //inicializa variavel
            $data = '';
            //carregar a chave publica do arquivo pem
            if (!$pubKey = file_get_contents($certFile)) {
                dd("Arquivo não encontrado - $certFile .");
            }
            //carrega o certificado em um array usando o LF como referencia
            $arCert = explode("\n", $pubKey);
            foreach ($arCert AS $curData) {
                //remove a tag de inicio e fim do certificado
                if (strncmp($curData, '-----BEGIN CERTIFICATE', 22) != 0 && strncmp($curData, '-----END CERTIFICATE', 20) != 0) {
                    //carrega o resultado numa string
                    $data .= trim($curData);
                }
            }
        } catch (\Exception $e) {
            dd($e->getMessage());
        }
        return $data;
    }

    public function retiraespacosCDATA($string)
    {
        $string = str_replace(" ", '', $string);
        return $string;
    }

    public function retira($string)
    {
        $string = (str_replace(array('-----BEGIN PUBLIC KEY-----',
            '-----END PUBLIC KEY-----', "\n"), '', $string));
        return $string;
    }

}

