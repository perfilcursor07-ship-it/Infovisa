{{-- STEP 1: DADOS DA SECRETARIA --}}
<div x-show="currentStep === 0" x-transition x-data="cnpjLookup()">
    <div class="mb-6">
        <h3 class="text-2xl font-bold text-blue-900 mb-2 flex items-center gap-2">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
            </svg>
            Passo 1: Dados da Secretaria
        </h3>
        <p class="text-gray-600">Preencha as informações da Secretaria de Saúde ou Vigilância Sanitária</p>
    </div>

    <div class="space-y-6">
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded">
            <p class="text-sm text-yellow-800">
                💡 <strong>Dica:</strong> Digite o CNPJ e clique em "Buscar Dados" para preencher automaticamente
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="md:col-span-2">
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    CNPJ <span class="text-red-500">*</span>
                </label>
                <div class="flex gap-2">
                    <input type="text" name="cnpj" x-model="cnpj" required
                           x-mask="99.999.999/9999-99"
                           placeholder="00.000.000/0000-00"
                           class="flex-1 px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <button type="button" @click="buscarCnpj()" 
                            class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold">
                        Buscar Dados
                    </button>
                </div>
                @error('cnpj')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    Razão Social <span class="text-red-500">*</span>
                </label>
                <input type="text" name="razao_social" x-model="razaoSocial" required
                       style="text-transform: uppercase;"
                       placeholder="Ex: SECRETARIA MUNICIPAL DE SAÚDE"
                       class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                @error('razao_social')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
            </div>
        </div>
    </div>
</div>

{{-- STEP 2: ENDEREÇO E CONTATO --}}
<div x-show="currentStep === 1" x-transition x-data="cepLookup()">
    <div class="mb-6">
        <h3 class="text-2xl font-bold text-green-900 mb-2 flex items-center gap-2">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            Passo 2: Endereço e Contato
        </h3>
        <p class="text-gray-600">Informe o endereço e dados de contato da secretaria</p>
    </div>

    <div class="space-y-6">
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded">
            <p class="text-sm text-yellow-800">
                💡 <strong>Dica:</strong> Digite o CEP e clique em "Buscar" para preencher automaticamente
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    CEP
                </label>
                <div class="flex gap-2">
                    <input type="text" name="cep" x-model="cep"
                           x-mask="99999-999"
                           placeholder="00000-000"
                           class="flex-1 px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <button type="button" @click="buscarCep()" 
                            class="px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold">
                        Buscar
                    </button>
                </div>
                @error('cep')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    Município
                </label>
                <select name="municipio_id" x-model="municipioId"
                        class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Selecione...</option>
                    @foreach($municipios as $municipio)
                        <option value="{{ $municipio->id }}">{{ $municipio->nome }}</option>
                    @endforeach
                </select>
                @error('municipio_id')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
            </div>

            <div class="md:col-span-3">
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    Endereço Completo
                </label>
                <input type="text" name="endereco" x-model="endereco"
                       style="text-transform: uppercase;"
                       placeholder="Rua, Avenida, Número, Bairro, etc."
                       class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    Telefone
                </label>
                <input type="text" name="telefone" value="{{ old('telefone') }}"
                       x-mask="(99) 99999-9999"
                       placeholder="(00) 00000-0000"
                       class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                @error('telefone')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    E-mail
                </label>
                <input type="email" name="email" value="{{ old('email') }}"
                       placeholder="contato@secretaria.gov.br"
                       class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                @error('email')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
            </div>
        </div>
    </div>
</div>

{{-- STEP 3: RESPONSÁVEL --}}
<div x-show="currentStep === 2" x-transition>
    <div class="mb-6">
        <h3 class="text-2xl font-bold text-purple-900 mb-2 flex items-center gap-2">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
            Passo 3: Secretário(a) / Coordenador(a)
        </h3>
        <p class="text-gray-600">Dados do(a) Secretário(a) de Saúde ou Coordenador(a) da VISA</p>
    </div>

    <div class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="md:col-span-2">
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    Nome Completo do(a) Responsável
                </label>
                <input type="text" name="responsavel_nome" value="{{ old('responsavel_nome') }}"
                       style="text-transform: uppercase;"
                       placeholder="Digite o nome completo"
                       class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                @error('responsavel_nome')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    CPF do(a) Responsável
                </label>
                <input type="text" name="responsavel_cpf" value="{{ old('responsavel_cpf') }}"
                       x-mask="999.999.999-99"
                       placeholder="000.000.000-00"
                       class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                @error('responsavel_cpf')<span class="text-red-500 text-sm">{{ $message }}</span>@enderror
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    Cargo
                </label>
                <input type="text" name="responsavel_cargo" value="{{ old('responsavel_cargo') }}"
                       style="text-transform: uppercase;"
                       placeholder="Ex: SECRETÁRIO(A) DE SAÚDE"
                       class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
        </div>

        <div class="bg-blue-50 border-l-4 border-blue-400 p-4 rounded mt-6">
            <p class="text-sm text-blue-800">
                ℹ️ <strong>Informação:</strong> Após finalizar o cadastro, será gerado um PDF que deverá ser impresso, assinado e anexado ao processo de receituário.
            </p>
        </div>
    </div>
</div>

<script>
function cnpjLookup() {
    return {
        cnpj: '{{ old("cnpj") }}',
        razaoSocial: '{{ old("razao_social") }}',
        
        async buscarCnpj() {
            const cnpjLimpo = this.cnpj.replace(/\D/g, '');
            if (cnpjLimpo.length !== 14) {
                alert('CNPJ inválido');
                return;
            }
            
            try {
                const response = await fetch(`{{ $rotaBuscarCnpj ?? route('admin.receituarios.buscar-cnpj') }}?cnpj=${cnpjLimpo}`);
                const data = await response.json();
                
                if (data.error) {
                    alert(data.error);
                    return;
                }
                
                this.razaoSocial = data.razao_social || '';
                
                // Dispara evento para preencher CEP no próximo step
                if (data.cep) {
                    window.cnpjData = data;
                }
                
                alert('Dados encontrados! Avance para o próximo passo.');
            } catch (error) {
                alert('Erro ao buscar CNPJ');
            }
        }
    };
}

function cepLookup() {
    return {
        cep: '{{ old("cep") }}',
        endereco: '{{ old("endereco") }}',
        municipioId: '{{ old("municipio_id") }}',
        
        init() {
            // Preenche com dados do CNPJ se disponível
            if (window.cnpjData) {
                this.cep = window.cnpjData.cep || '';
                this.endereco = window.cnpjData.endereco || '';
            }
        },
        
        async buscarCep() {
            const cepLimpo = this.cep.replace(/\D/g, '');
            if (cepLimpo.length !== 8) {
                alert('CEP inválido');
                return;
            }
            
            try {
                const response = await fetch(`https://viacep.com.br/ws/${cepLimpo}/json/`);
                const data = await response.json();
                
                if (data.erro) {
                    alert('CEP não encontrado');
                    return;
                }
                
                this.endereco = `${data.logradouro}, ${data.bairro}`.toUpperCase();
                
                // Buscar município
                const municipios = @json($municipios);
                const municipio = municipios.find(m => 
                    m.nome.toLowerCase().includes(data.localidade.toLowerCase())
                );
                if (municipio) {
                    this.municipioId = municipio.id;
                }
            } catch (error) {
                alert('Erro ao buscar CEP');
            }
        }
    };
}
</script>

