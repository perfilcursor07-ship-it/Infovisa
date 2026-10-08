<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Ficha Cadastral - Médico/Dentista/Veterinário</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 18mm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            font-size: 11pt;
            line-height: 1.3;
            color: #111827;
        }
        
        .container {
            width: 80%;
            margin: 0 auto;
            padding: 12mm 0 10mm;
        }
        
        .header {
            text-align: center;
            font-weight: bold;
            font-size: 12pt;
            line-height: 1.35;
            margin-bottom: 10px;
            padding: 10px 12px;
            border: 1.5px solid #111827;
            background-color: #eef0f2;
        }
        
        .section-title {
            background-color: #e9edf1;
            padding: 7px 9px;
            font-weight: bold;
            font-size: 9.5pt;
            text-align: center;
            text-transform: uppercase;
            border: 1px solid #4b5563;
            border-left: 3px solid #111827;
            margin-top: 11px;
            margin-bottom: 0;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        tr {
            page-break-inside: avoid;
        }
        
        td {
            border: 1px solid #4b5563;
            padding: 7px 8px;
            vertical-align: top;
        }
        
        .label {
            color: #374151;
            font-size: 8pt;
            font-weight: bold;
            margin-bottom: 3px;
            text-transform: uppercase;
        }
        
        .value {
            font-size: 10pt;
            min-height: 17px;
            overflow-wrap: break-word;
        }
        
        .signature-section {
            margin-top: 15px;
            border: 1px solid #000;
            padding: 10px;
        }
        
        .signature-boxes {
            display: table;
            width: 100%;
            margin-top: 8px;
        }
        
        .signature-box {
            display: table-cell;
            width: 33.33%;
            text-align: center;
            padding: 6px;
            border: 1px solid #4b5563;
            height: 120px;
            vertical-align: bottom;
        }
        
        .signature-label {
            font-size: 8pt;
            line-height: 1.4;
            margin-top: 5px;
        }
        
        .note {
            font-size: 9pt;
            font-style: italic;
            line-height: 1.4;
            margin-top: 8px;
            padding: 8px 9px;
            border: 1px solid #6b7280;
            background-color: #f3f4f6;
        }
        
        .two-columns {
            display: table;
            width: 100%;
        }
        
        .column {
            display: table-cell;
            width: 50%;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            FICHA CADASTRAL PARA MÉDICO, CIR. DENTISTA E MÉDICO VETERINÁRIO
        </div>

        <div class="section-title">
            *DADOS PESSOAIS QUE DEVEM SER IMPRESSOS NA NOTIFICAÇÃO DE RECEITA port. 344/98 Art. 55 alínea a.
        </div>

        <table>
            <tr>
                <td style="width: 60%;">
                    <div class="label">Nome:</div>
                    <div class="value">{{ $receituario->nome ?? '' }}</div>
                </td>
                <td style="width: 40%;">
                    <div class="label">CPF:</div>
                    <div class="value">{{ $receituario->cpf ?? '' }}</div>
                </td>
            </tr>
        </table>

        <table>
            <tr>
                <td style="width: 40%;">
                    <div class="label">Especialidade:</div>
                    <div class="value">{{ $receituario->especialidade ?? '' }}</div>
                </td>
                <td style="width: 30%;">
                    <div class="label">Telefone:</div>
                    <div class="value">{{ $receituario->telefone ?? '' }}</div>
                </td>
                <td style="width: 30%;">
                    <div class="label">Nº / Cons. de Classe:</div>
                    <div class="value">{{ $receituario->numero_conselho_classe ?? '' }}</div>
                </td>
            </tr>
        </table>

        <table>
            <tr>
                <td style="width: 50%;">
                    <div class="label">Endereço:</div>
                    <div class="value">{{ $receituario->endereco ?? '' }}</div>
                </td>
                <td style="width: 25%;">
                    <div class="label">CEP:</div>
                    <div class="value">{{ $receituario->cep ?? '' }}</div>
                </td>
                <td style="width: 25%;">
                    <div class="label">Município:</div>
                    <div class="value">{{ $receituario->municipio?->nome ?? $receituario->municipio ?? '' }}</div>
                </td>
            </tr>
        </table>

        <div class="section-title">
            LOCAIS DE TRABALHO
        </div>

        <table>
            @if($receituario->locais_trabalho && count($receituario->locais_trabalho) > 0)
                @foreach($receituario->locais_trabalho as $local)
                    @if(!empty($local['nome']))
                    <tr>
                        <td style="width: 70%;">
                            <div class="label">Nome:</div>
                            <div class="value">{{ $local['nome'] ?? '' }}</div>
                        </td>
                        <td style="width: 30%;">
                            <div class="label">Município:</div>
                            <div class="value">{{ $local['municipio'] ?? '' }}</div>
                        </td>
                    </tr>
                    @endif
                @endforeach
                @if(count($receituario->locais_trabalho) < 2)
                    @for($i = count($receituario->locais_trabalho); $i < 2; $i++)
                    <tr>
                        <td style="width: 70%;">
                            <div class="label">Nome:</div>
                            <div class="value">&nbsp;</div>
                        </td>
                        <td style="width: 30%;">
                            <div class="label">Município:</div>
                            <div class="value">&nbsp;</div>
                        </td>
                    </tr>
                    @endfor
                @endif
            @else
                <tr>
                    <td style="width: 70%;">
                        <div class="label">Nome:</div>
                        <div class="value">&nbsp;</div>
                    </td>
                    <td style="width: 30%;">
                        <div class="label">Município:</div>
                        <div class="value">&nbsp;</div>
                    </td>
                </tr>
                <tr>
                    <td style="width: 70%;">
                        <div class="label">Nome:</div>
                        <div class="value">&nbsp;</div>
                    </td>
                    <td style="width: 30%;">
                        <div class="label">Município:</div>
                        <div class="value">&nbsp;</div>
                    </td>
                </tr>
            @endif
        </table>

        <div class="section-title">
            ASSINATURAS
        </div>

        <div class="signature-boxes">
            <div class="signature-box">
                <div style="height: 100px;"></div>
                <div class="signature-label"><strong>1ª VIA</strong><br>Assinatura sem carimbar</div>
            </div>
            <div class="signature-box">
                <div style="height: 100px;"></div>
                <div class="signature-label"><strong>2ª VIA</strong><br>Assinatura sem carimbar</div>
            </div>
            <div class="signature-box">
                <div style="height: 100px;"></div>
                <div class="signature-label"><strong>3ª VIA</strong><br>Assinatura sem carimbar</div>
            </div>
        </div>

        <div class="note">
            <strong>Atenção:</strong> As assinaturas desta ficha devem ser semelhante a do documento de identificação ou deve ser reconhecida em cartório.
        </div>
    </div>
</body>
</html>
