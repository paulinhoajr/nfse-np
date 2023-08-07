public function geraNota($id)
{
ini_set('max_execution_time', 0);

$payment = Payment::findOrFail($id);

if ($payment->nfse_id != null) {
return false;
}

$user = User::where('id', $payment->user_id)->first();

if ($user == null) {
return false;
}

$ultimaNota = $this->nfse->orderBy('id', 'desc')->first();

if ($ultimaNota == null)
return false;

try {
$retorno = $this->notas->criarNfse($payment, $user, $ultimaNota);
if ($retorno != '') {

$retorno = str_replace('&lt;', '<', $retorno);
$retorno = str_replace('&gt;', '>', $retorno);
$retorno = str_replace('<?xml version="1.0" encoding="utf-8"?>', '', $retorno);
//$xmlresp = utf8_encode($retorno);
$doc = new \DOMDocument(); //cria objeto DOM
$doc->formatOutput = false;
$doc->preserveWhiteSpace = false;
$doc->loadXML($retorno, LIBXML_NOBLANKS | LIBXML_NOEMPTYTAG);

//$DataRecebimento = $doc->getElementsByTagName('DataRecebimento')->item(0)->nodeValue;
$Codigo = $doc->getElementsByTagName('Codigo')->item(0)->nodeValue;
$Mensagem = $doc->getElementsByTagName('Mensagem')->item(0)->nodeValue;
$Correcaor = $doc->getElementsByTagName('Correcao')->item(0)->nodeValue;

if ($Correcaor != "") {
return false;
}

$NumeroLote = $doc->getElementsByTagName('NumeroLote')->item(0)->nodeValue;
$Protocolo = $doc->getElementsByTagName('Protocolo')->item(0)->nodeValue;

if ($Protocolo != '') {

$data = [
'numero_lote' => $NumeroLote,
'numero_rps' => $NumeroLote,
'protocolo' => $Protocolo,
'codigo' => $Codigo,
'message' => $Mensagem,
'status' => 1
];

$nfse = $this->nfse->create($data);

$payment->update([
'nfse_id' => $nfse->id
]);

Log::create(['user_id' => null, 'name' => 'Gerar Nota Admin', 'description' => "Nota gerada com sucesso pagamento ID#" . $payment->id]);

sleep(1);

return true;
//echo "Gerado!<br>";
//return redirect()->back()->with('flash_message', 'Nfse gerada com sucesso!');
}

}
} catch (\Exception $e) {
return false;
}


}