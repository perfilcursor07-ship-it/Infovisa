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
        
        .section {
            margin-bottom: 10px;
        }
        
        .info-grid {
            font-size: 10pt;
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
            font-size: 10pt;
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

        .content p,
        .content .MsoNormal,
        .content div,
        .content li,
        .content h1,
        .content h2,
        .content h3,
        .content h4,
        .content h5,
        .content h6 {
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
        
        .preview-notice {
            margin-top: 20px;
            padding: 0;
            border: none;
            background: transparent;
            text-align: center;
        }
        
        .preview-notice-title {
            font-weight: bold;
            font-size: 6pt;
            color: #000;
            margin-bottom: 5px;
        }
        
        .preview-notice-text {
            font-size: 6pt;
            color: #000;
        }
        
        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: none;
            text-align: center;
            font-size: 7.5pt;
            color: #666;
        }

        .footer img {
            max-width: 320px;
            max-height: 50px;
            height: auto;
            width: auto;
            display: block;
            margin: 0 auto 8px;
        }
    </style>
</head>
<body>
    {{-- Logomarca --}}
    @if(isset($logomarca) && $logomarca)
        <div class="logo-container">
            @php
                $logoPathRelativo = str_replace('storage/', '', $logomarca);
                $logoPath = public_path('storage/' . $logoPathRelativo);
                
                if (file_exists($logoPath)) {
                    $logoData = base64_encode(file_get_contents($logoPath));
                    $logoExtension = pathinfo($logoPath, PATHINFO_EXTENSION);
                    $logoMimeType = $logoExtension === 'svg' ? 'svg+xml' : $logoExtension;
                    echo '<img src="data:image/' . $logoMimeType . ';base64,' . $logoData . '" alt="Logomarca">';
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

    {{-- Aviso de Preview --}}
    <div class="preview-notice">
        <div class="preview-notice-title">DOCUMENTO EM VISUALIZAÇÃO</div>
        <div class="preview-notice-text">
            Este é um preview do documento. Após a assinatura, o documento final incluirá as assinaturas eletrônicas e código de autenticidade.
        </div>
    </div>

    {{-- Rodapé --}}
    <div class="footer">
        @php
            $rodapeDocumento = $rodapeDocumento ?? null;

            if (!$rodapeDocumento) {
                $municipioRodape = null;

                if (isset($estabelecimento) && $estabelecimento && !$estabelecimento->isCompetenciaEstadual() && $estabelecimento->municipio_id) {
                    $municipioRodapeObj = $estabelecimento->municipioRelacionado ?? $estabelecimento->municipio ?? null;

                    if (!$municipioRodapeObj) {
                        $municipioRodapeObj = \App\Models\Municipio::find($estabelecimento->municipio_id);
                    }

                    if ($municipioRodapeObj && !empty($municipioRodapeObj->rodape_documento)) {
                        $municipioRodape = $municipioRodapeObj->rodape_documento;
                    }
                }

                $rodapeDocumento = $municipioRodape ?: \App\Models\ConfiguracaoSistema::rodapeEstadual();
            }

            $rodapePreviewPath = $rodapeDocumento ? public_path(ltrim($rodapeDocumento, '/')) : null;
        @endphp

        @if($rodapePreviewPath && file_exists($rodapePreviewPath))
            @php
                $rodapePreviewData = base64_encode(file_get_contents($rodapePreviewPath));
                $rodapePreviewExtension = pathinfo($rodapePreviewPath, PATHINFO_EXTENSION);
                $rodapePreviewMimeType = $rodapePreviewExtension === 'svg' ? 'svg+xml' : $rodapePreviewExtension;
            @endphp
            <img src="data:image/{{ $rodapePreviewMimeType }};base64,{{ $rodapePreviewData }}" alt="Rodapé do documento">
        @endif

        Documento gerado eletronicamente pelo Sistema InfoVISA em {{ $documento->created_at->format('d/m/Y H:i:s') }}
    </div>
</body>
</html>
