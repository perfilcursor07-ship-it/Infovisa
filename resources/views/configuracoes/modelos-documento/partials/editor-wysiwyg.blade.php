@php
    $variaveisModeloDocumento = [
        'Estabelecimento' => [
            '{estabelecimento_nome}' => 'Nome/Fantasia',
            '{estabelecimento_razao_social}' => 'Razão Social',
            '{estabelecimento_cnpj}' => 'CNPJ',
            '{estabelecimento_cpf}' => 'CPF',
            '{estabelecimento_endereco}' => 'Endereço completo',
            '{estabelecimento_bairro}' => 'Bairro',
            '{estabelecimento_cidade}' => 'Cidade',
            '{municipio}' => 'Município',
            '{estabelecimento_telefone}' => 'Telefone',
            '{estabelecimento_email}' => 'E-mail',
            '{atividades}' => 'Lista de atividades',
        ],
        'Responsável Legal' => [
            '{responsavel_legal_nome}' => 'Nome',
            '{responsavel_legal_cpf}' => 'CPF',
            '{responsavel_legal_email}' => 'E-mail',
            '{responsavel_legal_telefone}' => 'Telefone',
        ],
        'Responsável Técnico' => [
            '{responsavel_tecnico_nome}' => 'Nome',
            '{responsavel_tecnico_cpf}' => 'CPF',
            '{responsavel_tecnico_email}' => 'E-mail',
            '{responsavel_tecnico_telefone}' => 'Telefone',
            '{responsavel_tecnico_conselho}' => 'Nº Conselho',
        ],
        'Processo' => [
            '{processo_numero}' => 'Número',
            '{processo_tipo}' => 'Tipo',
            '{processo_data_criacao}' => 'Data de criação',
        ],
        'Data' => [
            '{data_atual}' => 'Data atual (dd/mm/aaaa)',
            '{data_extenso}' => 'Data por extenso',
            '{data_extenso_maiusculo}' => 'Data por extenso MAIÚSCULO',
            '{ano_atual}' => 'Ano atual',
        ],
    ];
@endphp

{{-- O mesmo Quill 2 local usado na criação e edição dos documentos digitais. --}}
<div x-data="modeloEditor()" class="editor-container">
    <input type="hidden" name="conteudo" x-model="conteudo">

    <div class="modelo-documento-variaveis mb-2 flex items-center gap-2 flex-wrap relative">
        <div class="relative" x-data="{ showVars: false }">
            <button type="button"
                    @click="showVars = !showVars"
                    class="px-3 py-1.5 text-sm bg-amber-100 hover:bg-amber-200 text-amber-800 rounded transition-all flex items-center gap-1"
                    title="Inserir variável">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                </svg>
                Inserir Variáveis
            </button>

            <div x-show="showVars"
                 @click.away="showVars = false"
                 class="absolute z-30 mt-1 w-80 bg-white border border-gray-300 rounded-lg shadow-xl p-2 max-h-80 overflow-y-auto"
                 style="display: none;">
                <p class="text-xs text-gray-500 mb-2 px-2 font-medium">Clique para inserir no documento:</p>

                @foreach($variaveisModeloDocumento as $grupo => $variaveis)
                    <div class="mb-2 last:mb-0">
                        <p class="text-xs font-bold text-gray-700 px-2 py-1 bg-gray-100 rounded">{{ $grupo }}</p>
                        <div class="space-y-0.5 mt-1">
                            @foreach($variaveis as $codigo => $descricao)
                                <button type="button"
                                        @click="inserirVariavel('{{ $codigo }}'); showVars = false"
                                        class="w-full text-left px-2 py-1 text-sm hover:bg-amber-50 rounded">
                                    <span class="font-mono text-amber-600 text-xs">{{ $codigo }}</span>
                                    <span class="text-gray-500 text-xs ml-1">- {{ $descricao }}</span>
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <textarea x-ref="editor" aria-label="Conteúdo do modelo de documento"></textarea>

    @error('conteudo')
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror

    <p class="mt-2 text-xs text-gray-500 flex items-center gap-1">
        <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        Use o botão "Inserir Variáveis" para adicionar campos que serão substituídos automaticamente ao gerar o documento.
    </p>
</div>

<script>
function modeloEditor() {
    // O editor (Quill) fica FORA dos dados reativos do Alpine: se for guardado no estado,
    // o Alpine o envolve num Proxy e a seleção do Quill quebra ("reading 'offset'"),
    // impedindo que as edições cheguem ao campo "conteudo".
    let editor = null;

    return {
        conteudo: @json(old('conteudo', $conteudoInicial ?? '')),

        init() {
            this.$nextTick(() => {
                editor = new window.DocumentoRichEditor(this.$refs.editor, {
                    height: 700,
                    images_upload_url: @json(route('admin.documentos.upload-imagem')),
                    images_upload_handler: (blobInfo) => this.enviarImagem(blobInfo),
                });

                editor.setContent(this.conteudo);
                editor.on('input change keyup', () => {
                    this.conteudo = editor.getContent();
                });

                // Garante o conteúdo mais recente no envio do formulário
                this.$el.closest('form')?.addEventListener('submit', () => {
                    this.conteudo = editor.getContent();
                    const campo = this.$el.querySelector('input[name="conteudo"]');
                    if (campo) campo.value = this.conteudo;
                });
            });
        },

        inserirVariavel(variavel) {
            if (!editor) return;
            editor.insertContent(variavel);
            this.conteudo = editor.getContent();
        },

        async enviarImagem(blobInfo) {
            const formData = new FormData();
            formData.append('file', blobInfo.blob(), blobInfo.filename());
            formData.append('_token', @json(csrf_token()));

            const response = await fetch(@json(route('admin.documentos.upload-imagem')), {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': @json(csrf_token()),
                },
                body: formData,
            });
            const data = await response.json().catch(() => ({}));

            if (!response.ok || !data.location) {
                throw new Error(data.message || 'Não foi possível enviar a imagem.');
            }

            return data.location;
        },
    };
}
</script>
