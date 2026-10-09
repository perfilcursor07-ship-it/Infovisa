@extends('layouts.admin')

@section('title', 'Configuração WhatsApp')
@section('page-title', 'Configuração do WhatsApp')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">

    {{-- Alertas --}}
    @if(session('success'))
    <div class="bg-green-50 border border-green-200 rounded-lg p-4 flex items-center gap-3">
        <svg class="w-5 h-5 text-green-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <span class="text-green-800 text-sm">{{ session('success') }}</span>
    </div>
    @endif

    @if($errors->any())
    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
        <ul class="list-disc list-inside text-red-800 text-sm space-y-1">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <div x-data="whatsappConfig()" x-init="verificarStatus(); iniciarMonitoramento()">

        {{-- Status da Conexão --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                    <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
                    </svg>
                    Status da Conexão
                </h2>
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold"
                          :class="{
                              'bg-green-100 text-green-800': statusConexao === 'conectado',
                              'bg-yellow-100 text-yellow-800': statusConexao === 'aguardando_qr',
                              'bg-red-100 text-red-800': statusConexao === 'desconectado',
                              'bg-blue-100 text-blue-800': statusConexao === 'reconectando',
                              'bg-gray-100 text-gray-800': statusConexao === 'verificando'
                          }">
                        <span class="w-2 h-2 rounded-full"
                              :class="{
                                  'bg-green-500': statusConexao === 'conectado',
                                  'bg-yellow-500 animate-pulse': statusConexao === 'aguardando_qr',
                                  'bg-red-500': statusConexao === 'desconectado',
                                  'bg-blue-500 animate-pulse': statusConexao === 'reconectando',
                                  'bg-gray-400 animate-pulse': statusConexao === 'verificando'
                              }"></span>
                        <span x-text="statusTexto"></span>
                    </span>
                </div>
            </div>

            <div class="flex flex-wrap gap-3">
                <button @click="verificarStatus()" 
                        :disabled="carregando"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors text-sm font-medium disabled:opacity-50">
                    <svg class="w-4 h-4" :class="{'animate-spin': carregando}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Verificar Status
                </button>

                <button @click="iniciarSessao()" 
                        :disabled="carregando || statusConexao === 'conectado'"
                        x-show="statusConexao !== 'conectado'"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-sm font-medium disabled:opacity-50">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    Conectar WhatsApp
                </button>

                <button @click="encerrarSessao()" 
                        :disabled="carregando"
                        x-show="statusConexao === 'conectado'"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors text-sm font-medium disabled:opacity-50">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                    </svg>
                    Desconectar
                </button>
            </div>

            {{-- QR Code --}}
            <div x-show="qrCode" x-transition class="mt-4 p-4 bg-gray-50 rounded-lg border border-gray-200 text-center">
                <p class="text-sm text-gray-600 mb-3">Escaneie o QR Code com o WhatsApp do celular:</p>
                <div class="inline-block bg-white p-4 rounded-lg shadow-sm">
                    <img :src="qrCode" alt="QR Code WhatsApp" class="w-64 h-64">
                </div>
                <p class="text-xs text-gray-500 mt-3">O QR Code será atualizado automaticamente a cada 15 segundos.</p>
            </div>

            {{-- Mensagem de feedback --}}
            <div x-show="feedbackMensagem" x-transition 
                 class="mt-4 p-3 rounded-lg text-sm"
                 :class="feedbackTipo === 'sucesso' ? 'bg-green-50 text-green-800 border border-green-200' : 'bg-red-50 text-red-800 border border-red-200'">
                <span x-text="feedbackMensagem"></span>
            </div>
        </div>

        {{-- Formulário de Configuração --}}
        <form action="{{ route('admin.whatsapp.configuracao.salvar') }}" method="POST">
            @csrf

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Configurações do Servidor
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {{-- URL do Servidor Baileys --}}
                    <div>
                        <label for="baileys_server_url" class="block text-sm font-medium text-gray-700 mb-1">
                            URL do Servidor Baileys <span class="text-red-500">*</span>
                        </label>
                        <input type="url" name="baileys_server_url" id="baileys_server_url"
                               value="{{ old('baileys_server_url', $config->baileys_server_url) }}"
                               placeholder="http://localhost:3000"
                               class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                        <p class="text-xs text-gray-500 mt-1">Endereço do servidor WhatsApp Baileys</p>
                    </div>

                    {{-- Chave de API --}}
                    <div>
                        <label for="api_key" class="block text-sm font-medium text-gray-700 mb-1">
                            Chave de API (opcional)
                        </label>
                        <input type="text" name="api_key" id="api_key"
                               value="{{ old('api_key', $config->api_key) }}"
                               placeholder="Chave de autenticação"
                               class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                        <p class="text-xs text-gray-500 mt-1">Se o servidor Baileys requer autenticação</p>
                    </div>

                    {{-- Nome da Sessão --}}
                    <div>
                        <label for="session_name" class="block text-sm font-medium text-gray-700 mb-1">
                            Nome da Sessão <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="session_name" id="session_name"
                               value="{{ old('session_name', $config->session_name) }}"
                               placeholder="infovisa"
                               class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                        <p class="text-xs text-gray-500 mt-1">Identificador único da sessão no servidor</p>
                    </div>

                    {{-- Switches --}}
                    <div class="space-y-3">
                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div>
                                <label for="ativo" class="text-sm font-medium text-gray-700">Ativar WhatsApp</label>
                                <p class="text-xs text-gray-500">Habilita o envio de mensagens</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="hidden" name="ativo" value="0">
                                <input type="checkbox" name="ativo" id="ativo" value="1"
                                       {{ old('ativo', $config->ativo) ? 'checked' : '' }}
                                       class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                            </label>
                        </div>

                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                            <div>
                                <label for="enviar_ao_assinar" class="text-sm font-medium text-gray-700">Enviar ao assinar</label>
                                <p class="text-xs text-gray-500">Envia quando todas assinaturas concluírem</p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="hidden" name="enviar_ao_assinar" value="0">
                                <input type="checkbox" name="enviar_ao_assinar" id="enviar_ao_assinar" value="1"
                                       {{ old('enviar_ao_assinar', $config->enviar_ao_assinar) ? 'checked' : '' }}
                                       class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Template da Mensagem --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-bold text-gray-900 flex items-center gap-2">
                        <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                        </svg>
                        Template da Mensagem
                    </h2>
                    <button type="button" @click="restaurarTemplate()"
                            class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                        Restaurar padrão
                    </button>
                </div>

                <div class="mb-3">
                    <textarea name="mensagem_template" id="mensagem_template" rows="12"
                              class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm font-mono">{{ old('mensagem_template', $config->mensagem_template) }}</textarea>
                </div>

                <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                    <p class="text-sm font-medium text-blue-800 mb-2">Variáveis disponíveis:</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-1">
                        <code class="text-xs text-blue-700 bg-blue-100 px-2 py-1 rounded">{nome_usuario}</code>
                        <span class="text-xs text-blue-600">Nome do usuário externo</span>
                        <code class="text-xs text-blue-700 bg-blue-100 px-2 py-1 rounded">{nome_documento}</code>
                        <span class="text-xs text-blue-600">Tipo do documento</span>
                        <code class="text-xs text-blue-700 bg-blue-100 px-2 py-1 rounded">{numero_documento}</code>
                        <span class="text-xs text-blue-600">Número do documento</span>
                        <code class="text-xs text-blue-700 bg-blue-100 px-2 py-1 rounded">{nome_estabelecimento}</code>
                        <span class="text-xs text-blue-600">Nome do estabelecimento</span>
                        <code class="text-xs text-blue-700 bg-blue-100 px-2 py-1 rounded">{link_documento}</code>
                        <span class="text-xs text-blue-600">Link para verificar o documento</span>
                    </div>
                </div>
            </div>

            {{-- Teste de Envio --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                    <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                    </svg>
                    Teste de Envio
                </h2>

                <div class="flex items-end gap-3">
                    <div class="flex-1">
                        <label for="telefone_teste" class="block text-sm font-medium text-gray-700 mb-1">Telefone para teste</label>
                        <input type="text" id="telefone_teste" x-model="telefoneTeste"
                               placeholder="(63) 99999-9999"
                               class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                    </div>
                    <button type="button" @click="enviarTeste()"
                            :disabled="carregandoTeste || !telefoneTeste"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors text-sm font-medium disabled:opacity-50 whitespace-nowrap">
                        <svg class="w-4 h-4" :class="{'animate-spin': carregandoTeste}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                        </svg>
                        <span x-text="carregandoTeste ? 'Enviando...' : 'Enviar Teste'"></span>
                    </button>
                </div>

                <div x-show="testeFeedback" x-transition class="mt-3 p-3 rounded-lg text-sm"
                     :class="testeTipo === 'sucesso' ? 'bg-green-50 text-green-800 border border-green-200' : 'bg-red-50 text-red-800 border border-red-200'">
                    <span x-text="testeFeedback"></span>
                </div>
            </div>

            {{-- Botões --}}
            <div class="flex items-center justify-between">
                <a href="{{ route('admin.configuracoes.index') }}" 
                   class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors text-sm font-medium">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Voltar
                </a>
                <div>
                    <button type="submit" 
                            class="inline-flex items-center gap-2 px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm font-medium shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Salvar Configurações
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function whatsappConfig() {
    return {
        statusConexao: '{{ $config->status_conexao }}',
        qrCode: null,
        carregando: false,
        carregandoTeste: false,
        feedbackMensagem: '',
        feedbackTipo: '',
        telefoneTeste: '',
        testeFeedback: '',
        testeTipo: '',
        pollingInterval: null,
        autoReconectando: false,
        monitorInterval: null,

        get statusTexto() {
            const textos = {
                'conectado': 'Conectado',
                'desconectado': 'Desconectado',
                'aguardando_qr': 'Aguardando QR Code',
                'verificando': 'Verificando...',
                'reconectando': 'Reconectando...'
            };
            return textos[this.statusConexao] || this.statusConexao;
        },

        async verificarStatus() {
            this.carregando = true;
            this.statusConexao = 'verificando';
            try {
                const response = await fetch('{{ route("admin.whatsapp.status") }}');
                const data = await response.json();
                this.statusConexao = data.status || 'desconectado';
                this.qrCode = data.qr_code || data.qr || null;
                if (this.statusConexao === 'aguardando_qr') {
                    this.iniciarPolling();
                } else {
                    this.pararPolling();
                }
                if (data.mensagem && !data.sucesso) {
                    this.mostrarFeedback(data.mensagem, 'erro');
                }
                // Auto-reconectar se desconectou
                if (this.statusConexao === 'desconectado' && !this.autoReconectando) {
                    this.tentarAutoReconectar();
                }
            } catch (e) {
                this.statusConexao = 'desconectado';
                this.mostrarFeedback('Erro ao verificar status: ' + e.message, 'erro');
            }
            this.carregando = false;
        },

        async tentarAutoReconectar() {
            this.autoReconectando = true;
            this.statusConexao = 'reconectando';
            this.mostrarFeedback('Desconectado. Tentando reconectar em 3 segundos...', 'aviso');
            
            await new Promise(r => setTimeout(r, 3000));
            
            try {
                const response = await fetch('{{ route("admin.whatsapp.iniciar-sessao") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const data = await response.json();
                if (data.sucesso) {
                    this.qrCode = data.qr_code || data.qr || null;
                    this.statusConexao = data.qr_code ? 'aguardando_qr' : 'conectado';
                    this.mostrarFeedback('Reconectando... ' + (data.mensagem || ''), 'sucesso');
                    this.iniciarPolling();
                } else {
                    this.statusConexao = 'desconectado';
                    this.mostrarFeedback('Falha ao reconectar: ' + (data.mensagem || ''), 'erro');
                }
            } catch (e) {
                this.statusConexao = 'desconectado';
                this.mostrarFeedback('Erro ao reconectar: ' + e.message, 'erro');
            }
            this.autoReconectando = false;
        },

        iniciarMonitoramento() {
            this.pararMonitoramento();
            // Verifica status a cada 60 segundos
            this.monitorInterval = setInterval(() => {
                if (this.statusConexao === 'conectado') {
                    this.verificarStatusSilencioso();
                }
            }, 60000);
        },

        pararMonitoramento() {
            if (this.monitorInterval) {
                clearInterval(this.monitorInterval);
                this.monitorInterval = null;
            }
        },

        async verificarStatusSilencioso() {
            try {
                const response = await fetch('{{ route("admin.whatsapp.status") }}');
                const data = await response.json();
                const novoStatus = data.status || 'desconectado';
                if (novoStatus !== 'conectado' && this.statusConexao === 'conectado') {
                    this.statusConexao = novoStatus;
                    this.tentarAutoReconectar();
                }
            } catch (e) {
                // Silencioso - não mostra erro
            }
        },

        async iniciarSessao() {
            this.carregando = true;
            try {
                const response = await fetch('{{ route("admin.whatsapp.iniciar-sessao") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const data = await response.json();
                if (data.sucesso) {
                    this.qrCode = data.qr_code || data.qr || null;
                    this.statusConexao = 'aguardando_qr';
                    this.mostrarFeedback(data.mensagem, 'sucesso');
                    this.iniciarPolling();
                } else {
                    this.mostrarFeedback(data.mensagem, 'erro');
                }
            } catch (e) {
                this.mostrarFeedback('Erro ao iniciar sessão: ' + e.message, 'erro');
            }
            this.carregando = false;
        },

        async encerrarSessao() {
            if (!confirm('Deseja realmente desconectar o WhatsApp?')) return;
            this.carregando = true;
            try {
                const response = await fetch('{{ route("admin.whatsapp.encerrar-sessao") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const data = await response.json();
                this.statusConexao = 'desconectado';
                this.qrCode = null;
                this.pararPolling();
                this.mostrarFeedback(data.mensagem, data.sucesso ? 'sucesso' : 'erro');
            } catch (e) {
                this.mostrarFeedback('Erro ao encerrar sessão: ' + e.message, 'erro');
            }
            this.carregando = false;
        },

        async restaurarTemplate() {
            try {
                const response = await fetch('{{ route("admin.whatsapp.restaurar-template") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                const data = await response.json();
                if (data.sucesso) {
                    document.getElementById('mensagem_template').value = data.template;
                    this.mostrarFeedback('Template restaurado ao padrão.', 'sucesso');
                }
            } catch (e) {
                this.mostrarFeedback('Erro ao restaurar template.', 'erro');
            }
        },

        async enviarTeste() {
            this.carregandoTeste = true;
            this.testeFeedback = '';
            try {
                const response = await fetch('{{ route("admin.whatsapp.enviar-teste") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ telefone: this.telefoneTeste })
                });
                const data = await response.json();
                this.testeFeedback = data.mensagem || data.message;
                this.testeTipo = data.sucesso ? 'sucesso' : 'erro';
            } catch (e) {
                this.testeFeedback = 'Erro ao enviar teste: ' + e.message;
                this.testeTipo = 'erro';
            }
            this.carregandoTeste = false;
        },

        iniciarPolling() {
            this.pararPolling();
            this.pollingInterval = setInterval(() => {
                this.verificarStatus();
                if (this.statusConexao === 'conectado') {
                    this.pararPolling();
                }
            }, 15000);
        },

        pararPolling() {
            if (this.pollingInterval) {
                clearInterval(this.pollingInterval);
                this.pollingInterval = null;
            }
        },

        mostrarFeedback(mensagem, tipo) {
            this.feedbackMensagem = mensagem;
            this.feedbackTipo = tipo;
            setTimeout(() => { this.feedbackMensagem = ''; }, 8000);
        }
    }
}
</script>
@endsection
