<?php

    class BrapiService {
    
    private string $token = "m8Lt9Ki9sMMWptSGTyu5js"; 
    private string $baseUrl = "https://brapi.dev/api";

    /**
     * Busca dados de uma ou mais ações/FIIs na Brapi
     * Exemplo de $tickers: 'PETR4' ou 'PETR4,VALE3'
     */
    public function buscarCotacao(string $tickers): array {
        $url = "{$this->baseUrl}/quote/{$tickers}?token={$this->token}";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Apenas se necessário em ambiente local

        $resposta = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$resposta) {
            return ['erro' => 'Não foi possível buscar cotações na Brapi..'];
        }

        return json_decode($resposta, true);
    }
}