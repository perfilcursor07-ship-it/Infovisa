<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $documento->tipoDocumento->nome }} - {{ $documento->numero_documento }}</title>
    <style>
        @page {
            margin: 20mm 15mm;
            size: A4;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 8pt;
            line-height: 1.3;
            color: #000;
            padding: 10px 20px;
        }
        
        .logo-container {
            text-align: center;
            margin-bottom: 10px;
        }
        
        .logo-container img {
            max-height: 100px;
            max-width: 400px;
            height: auto;
            width: auto;
        }
        
        .header {
            text-align: center;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 1px solid #d0d0d0;
        }
        
        .header h1 {
            font-size: 14pt;
            font-weight: bold;
            margin-bottom: 2px;
            text-transform: uppercase;
        }
        
        .header .numero {
            font-size: 14pt;
            font-weight: bold;
            margin-bottom: 3px;
        }
        
        .header .processo {
            font-size: 8pt;
        }
        
        .qrcode-container {
            position: absolute;
            top: 10px;
            right: 15px;
            text-align: center;
        }
        
        .qrcode-container img {
            width: 80px;
            height: 80px;
        }
        
        .qrcode-container p {
            font-size: 6pt;
            margin-top: 2px;
        }
        
        .section {
            margin-bottom: 10px;
        }
        
        .section-title {
            font-size: 9pt;
            font-weight: bold;
            margin-bottom: 5px;
            padding-bottom: 2px;
            border-bottom: 1px solid #ccc;
        }

        .content div,
        .content li,
        .content h1,
        .content h2,
        .content h3,
        .content h4,
        .content h5,
        .content h6,
        .content p,
        .content .MsoNormal {
            margin: 0;
            padding: 0;
            line-height: 1.6;
            word-wrap: break-word;
        }

        .content ul,
        .content ol {
            margin: 0;
            padding-left: 1.5em;
        }
        
        .info-grid {
            font-size: 8pt;
            line-height: 1.5;
            color: #000;
        }

        .cabecalho-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            border: 1px solid #d0d0d0;
            font-size: 8pt;
        }

        .cabecalho-table td {
            padding: 6px 8px;
            vertical-align: top;
            word-wrap: break-word;
            border-bottom: 1px solid #eaeaea;
        }

        .cabecalho-table tr:last-child td {
            border-bottom: none;
        }

        .cabecalho-table td + td {
            border-left: 1px solid #eaeaea;
        }

        .cabecalho-label {
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .content {
            margin: 8px 0;
            padding: 0;
            border: none;
            min-height: 150px;
            font-size: 8pt;
            line-height: 1.6;
        }

        .ql-align-center { text-align: center; }
        .ql-align-right { text-align: right; }
        .ql-align-left { text-align: left; }
        .ql-align-justify { text-align: justify; }

        .content img {
            max-width: 100%;
            height: auto;
            display: inline-block;
            vertical-align: middle;
        }

        .content .ql-align-center img,
        .content img[data-align="center"] {
            display: inline-block;
        }

        .content table {
            border-collapse: collapse;
            width: 100%;
            table-layout: fixed;
            max-width: 100%;
            border: 1px solid #9ca3af;
            margin: 0.75rem 0;
        }

        .content table col {
            min-width: 0;
        }

        .content table td,
        .content table th {
            border: 1px solid #9ca3af;
            padding: 8px;
            word-wrap: break-word;
            overflow-wrap: break-word;
            vertical-align: middle;
        }

        .content table td img,
        .content table th img {
            display: inline-block !important;
            margin: 0 !important;
            max-width: 100%;
            height: auto;
            vertical-align: middle;
        }
        
        .signatures {
            margin-top: 15px;
            padding-top: 8px;
            border-top: none;
        }
        
        .signature-item {
            margin-bottom: 2px;
            padding: 0;
            border: none;
            font-size: 8pt;
            line-height: 1.2;
            color: #000;
        }
        
        .authenticity {
            margin-top: 12px;
            padding: 0;
            border: none;
            background: transparent;
        }
        
        .authenticity-title {
            font-weight: bold;
            margin-bottom: 3px;
            font-size: 6pt;
        }
        
        .authenticity-text {
            font-size: 6pt;
            line-height: 1.2;
        }
        
        .authenticity-code {
            font-family: 'Courier New', monospace;
            padding: 0;
            border: none;
            background: transparent;
            display: inline;
            margin-top: 0;
            font-size: 6pt;
            word-break: break-all;
        }
        
        .footer {
            margin-top: 30px;
            padding: 15px 0;
            border-top: 2px solid #333;
            font-size: 7.5pt;
            color: #000;
            width: 100%;
            overflow: hidden;
        }
        
        .footer-content {
            width: 100%;
            overflow: hidden;
        }
        
        .footer-logo {
            float: left;
            width: 70px;
            padding-right: 12px;
        }
        
        .footer-logo img {
            max-height: 50px;
            max-width: 70px;
            height: auto;
            width: auto;
            display: block;
        }
        
        .footer-text {
            overflow: hidden;
            text-align: justify;
            line-height: 1.5;
            word-wrap: break-word;
        }
    </style>
</head>
<body>
    {{-- QR Code --}}
    <div class="qrcode-container">
        <img src="data:image/png;base64,{{ $qrCodeBase64 }}" alt="QR Code">
        <p>Verificar<br>Autenticidade</p>
    </div>

    {{-- Logomarca --}}
    @if(isset($logomarca) && $logomarca)
        <div class="logo-container">
            @php
                $logoPathRelativo = str_replace('storage/', '', $logomarca);
                $logoPath = public_path('storage/' . $logoPathRelativo);
                
                if (file_exists($logoPath)) {
                    try {
                        // Lê a imagem
                        $imageData = file_get_contents($logoPath);
                        $imageInfo = getimagesize($logoPath);
                        
                        if ($imageInfo && $imageData) {
                            $mimeType = $imageInfo['mime'];
                            
                            // Usa tamanho real da imagem sem redimensionar
                            $base64 = base64_encode($imageData);
                            echo '<img src="data:' . $mimeType . ';base64,' . $base64 . '" alt="Logomarca" style="max-width: 100%; height: auto;">';
                        }
                    } catch (\Exception $e) {
                        // Ignora erros
                    }
                }
            @endphp
        </div>
    @endif

    {{-- Cabeçalho --}}
    <div class="header">
        <h1>{{ $documento->tipoDocumento->nome }}</h1>
        <div class="numero">{{ $documento->numero_documento }}</div>
    </div>

    {{-- Dados do Estabelecimento --}}
    @if($estabelecimento)
    <div class="section">
        <div class="info-grid">
            @php
                $responsaveisLegais = $estabelecimento->relationLoaded('responsaveisLegais')
                    ? $estabelecimento->responsaveisLegais
                    : $estabelecimento->responsaveisLegais()->get();
                $responsaveisTecnicos = $estabelecimento->relationLoaded('responsaveisTecnicos')
                    ? $estabelecimento->responsaveisTecnicos
                    : $estabelecimento->responsaveisTecnicos()->get();
                $responsavelLegal = $responsaveisLegais->first();
                $responsavelTecnico = $responsaveisTecnicos->first();
                $cpfResponsavel = fn ($responsavel) => $responsavel
                    ? ($responsavel->cpf_formatado ?? $responsavel->cpf ?? 'Não cadastrado')
                    : 'Não cadastrado';
                
                // Formatar CEP (00000-000)
                $cepFormatado = $estabelecimento->cep;
                if (strlen($cepFormatado) === 8) {
                    $cepFormatado = substr($cepFormatado, 0, 5) . '-' . substr($cepFormatado, 5);
                }
                
                // Formatar telefone (00) 0000-0000 ou (00) 00000-0000
                $telefoneFormatado = '';
                if ($estabelecimento->telefone) {
                    $tel = preg_replace('/[^0-9]/', '', $estabelecimento->telefone);
                    if (strlen($tel) === 10) {
                        $telefoneFormatado = '(' . substr($tel, 0, 2) . ') ' . substr($tel, 2, 4) . '-' . substr($tel, 6);
                    } elseif (strlen($tel) === 11) {
                        $telefoneFormatado = '(' . substr($tel, 0, 2) . ') ' . substr($tel, 2, 5) . '-' . substr($tel, 7);
                    } else {
                        $telefoneFormatado = $estabelecimento->telefone;
                    }
                }
                
                // Formatar celular
                $celularFormatado = '';
                if ($estabelecimento->celular) {
                    $cel = preg_replace('/[^0-9]/', '', $estabelecimento->celular);
                    if (strlen($cel) === 10) {
                        $celularFormatado = '(' . substr($cel, 0, 2) . ') ' . substr($cel, 2, 4) . '-' . substr($cel, 6);
                    } elseif (strlen($cel) === 11) {
                        $celularFormatado = '(' . substr($cel, 0, 2) . ') ' . substr($cel, 2, 5) . '-' . substr($cel, 7);
                    } else {
                        $celularFormatado = $estabelecimento->celular;
                    }
                }
            @endphp
            
            <table class="cabecalho-table">
                <tr>
                    <td>
                        <span class="cabecalho-label">Razão Social:</span>
                        {{ $estabelecimento->nome_razao_social }}
                    </td>
                    <td>
                        <span class="cabecalho-label">Nome Fantasia:</span>
                        {{ $estabelecimento->nome_fantasia ?? 'N/A' }}
                    </td>
                </tr>
                <tr>
                    <td>
                        <span class="cabecalho-label">{{ $estabelecimento->tipo_pessoa === 'juridica' ? 'CNPJ' : 'CPF' }}:</span>
                        {{ $estabelecimento->documento_formatado }}
                    </td>
                    <td>
                        <span class="cabecalho-label">Município:</span>
                        {{ $estabelecimento->cidade ?? 'N/A' }}
                    </td>
                </tr>
                <tr>
                    <td>
                        <span class="cabecalho-label">Endereço:</span>
                        {{ $estabelecimento->endereco }}, {{ $estabelecimento->numero }}@if($estabelecimento->complemento), {{ $estabelecimento->complemento }}@endif
                    </td>
                    <td>
                        <span class="cabecalho-label">CEP:</span>
                        {{ $cepFormatado }}
                    </td>
                </tr>
                <tr>
                    <td>
                        <span class="cabecalho-label">Bairro:</span>
                        {{ $estabelecimento->bairro ?? 'N/A' }}
                    </td>
                    <td>
                        <span class="cabecalho-label">Telefone:</span>
                        {{ $telefoneFormatado }}@if($celularFormatado), {{ $celularFormatado }}@endif
                    </td>
                </tr>
                <tr>
                    <td>
                        <span class="cabecalho-label">Responsável Legal:</span>
                        {{ $responsavelLegal?->nome ?? 'Não cadastrado' }}
                    </td>
                    <td>
                        <span class="cabecalho-label">CPF:</span>
                        {{ $cpfResponsavel($responsavelLegal) }}
                    </td>
                </tr>
                <tr>
                    <td>
                        <span class="cabecalho-label">Responsável Técnico:</span>
                        {{ $responsavelTecnico?->nome ?? 'Não cadastrado' }}
                    </td>
                    <td>
                        <span class="cabecalho-label">CPF:</span>
                        {{ $cpfResponsavel($responsavelTecnico) }}
                    </td>
                </tr>
            </table>
        </div>
    </div>
    @endif

    {{-- Conteúdo do Documento --}}
    <div class="section">
        <div class="content">
            {!! $documento->conteudoParaPdf() !!}
        </div>
    </div>

    {{-- Assinaturas Eletrônicas --}}
    @if($assinaturas->count() > 0)
    <div class="signatures">
        <div class="section-title">Assinaturas Eletrônicas</div>
        @foreach($assinaturas as $assinatura)
        <div class="signature-item">
            <strong>Assinado por {{ $assinatura->usuarioInterno->nome }}</strong> em {{ $assinatura->assinado_em->format('d/m/Y') }} às {{ $assinatura->assinado_em->format('H:i:s') }}
        </div>
        @endforeach
    </div>
    @endif

    {{-- Autenticidade --}}
    <div class="authenticity">
        <div class="authenticity-title">Verificação de Autenticidade</div>
        <div class="authenticity-text">
            A autenticidade deste documento pode ser conferida através do link:<br>
            <strong>{{ $urlAutenticidade }}</strong>
            <br><br>
            Caso necessário, o código do documento é:<br>
            <span class="authenticity-code">{{ $codigoAutenticidade }}</span>
        </div>
    </div>

    {{-- Rodapé --}}
    <div class="footer">
        <div class="footer-content">
            <div class="footer-logo">
                @php
                    try {
                        $rodapeDocumento = $rodapeDocumento ?? null;

                        if (!$rodapeDocumento) {
                            $municipioRodape = null;

                            if (isset($estabelecimento) && $estabelecimento && !$estabelecimento->isCompetenciaEstadual() && $estabelecimento->municipio_id) {
                                $municipioRodapeObj = null;

                                if (method_exists($estabelecimento, 'relationLoaded') && $estabelecimento->relationLoaded('municipio') && is_object($estabelecimento->getRelation('municipio'))) {
                                    $municipioRodapeObj = $estabelecimento->getRelation('municipio');
                                } else {
                                    $municipioRodapeObj = \App\Models\Municipio::find($estabelecimento->municipio_id);
                                }

                                if ($municipioRodapeObj && !empty($municipioRodapeObj->rodape_documento)) {
                                    $municipioRodape = $municipioRodapeObj->rodape_documento;
                                }
                            }

                            $rodapeDocumento = $municipioRodape ?: \App\Models\ConfiguracaoSistema::rodapeEstadual();
                        }

                        $rodapeImagePath = $rodapeDocumento ? public_path(ltrim($rodapeDocumento, '/')) : null;

                        if ($rodapeImagePath && file_exists($rodapeImagePath)) {
                            $imageData = file_get_contents($rodapeImagePath);
                            $imageInfo = getimagesize($rodapeImagePath);
                            
                            if ($imageInfo && $imageData) {
                                $mimeType = $imageInfo['mime'];
                                $base64 = base64_encode($imageData);
                                echo '<img src="data:' . $mimeType . ';base64,' . $base64 . '" alt="Rodapé" style="max-width: 100%; height: auto;">';
                            }
                        }
                    } catch (\Exception $e) {
                        // Ignora erros
                    }
                @endphp
            </div>
            <div class="footer-text">
                @php
                    $rodapeTexto = $rodapeTexto ?? null;

                    if (!$rodapeTexto) {
                        $municipioTexto = null;

                        if (isset($estabelecimento) && $estabelecimento && !$estabelecimento->isCompetenciaEstadual() && $estabelecimento->municipio_id) {
                            $municipioTextoObj = null;

                            if (method_exists($estabelecimento, 'relationLoaded') && $estabelecimento->relationLoaded('municipio') && is_object($estabelecimento->getRelation('municipio'))) {
                                $municipioTextoObj = $estabelecimento->getRelation('municipio');
                            } else {
                                $municipioTextoObj = \App\Models\Municipio::find($estabelecimento->municipio_id);
                            }

                            if ($municipioTextoObj && !empty($municipioTextoObj->rodape_texto)) {
                                $municipioTexto = $municipioTextoObj->rodape_texto;
                            }
                        }

                        $rodapeTexto = $municipioTexto ?: \App\Models\ConfiguracaoSistema::rodapeTextoPadrao();
                    }
                @endphp
                {!! nl2br(e($rodapeTexto)) !!}
            </div>
        </div>
    </div>
</body>
</html>
