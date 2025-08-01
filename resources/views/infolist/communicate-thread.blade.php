@php
    use Illuminate\Support\Facades\Storage;
    
    $messages = $getRecord()->getThreadMessages();
    $currentUser = auth()->user();
@endphp

<div class="space-y-6">
    @foreach($messages as $index => $message)
        @php
            $isMainMessage = $index === 0;
            $isCurrentUser = $message->sender_id === $currentUser->id;
        @endphp
        
        <!-- Message Item -->
        <div class="relative group @if(!$loop->last) after:absolute after:top-10 after:bottom-0 after:start-4 after:w-0.5 after:bg-gradient-to-b after:from-gray-300 after:to-gray-200 dark:after:from-gray-600 dark:after:to-gray-700 after:-translate-x-1/2 after:rounded-full @endif">
            <div class="w-full flex gap-x-4">
                <!-- Avatar/Icon com melhor destaque visual -->
                <div class="relative z-10 mt-1">
                    <span class="flex shrink-0 justify-center items-center size-8 bg-gray-100 text-gray-500 border-2 border-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-600 rounded-full shadow-sm">
                        @if($isMainMessage)
                            <x-filament::icon 
                                icon="heroicon-o-envelope" 
                                class="shrink-0 size-4" 
                            />
                        @else
                            <x-filament::icon 
                                icon="heroicon-o-chat-bubble-left" 
                                class="shrink-0 size-4" 
                            />
                        @endif
                    </span>
                </div>

                <div class="grow mt-1">
                    <!-- Message Header -->
                    <div class="flex flex-col gap-y-2">
                        <!-- Sender and Date -->
                        <div class="flex justify-between items-start">
                            <div class="flex flex-col items-start">
                                <!-- Linha visual ao lado do nome -->
                                <span class="font-medium @if($isCurrentUser) text-blue-600 dark:text-blue-400 @else text-gray-800 dark:text-gray-200 @endif">
                                    {{ $message->sender->name }}
                                </span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    @if($isMainMessage)
                                        {{ __('filament-communicate::default.view.thread.created_at') }} {{ $message->created_at->format('d/m/Y H:i') }}
                                    @else
                                        {{ __('filament-communicate::default.view.thread.replied_at') }} {{ $message->created_at->format('d/m/Y H:i') }}
                                    @endif
                                </span>
                            </div>
                            <div class="flex justify-start items-start space-x-1">
                                @if($isMainMessage && $message->messageType)
                                    <x-filament::badge color="info" icon="heroicon-o-tag">
                                        {{ $message->messageType->name }}
                                    </x-filament::badge>
                            @endif

                            @if($isMainMessage && $message->priority)
                                <x-filament::badge :color="$message->priority->getColor()" :icon="$message->priority->getIcon()">
                                    {{ $message->priority->getLabel() }}
                                </x-filament::badge>
                            @endif
                            </div>
                        </div>
                    </div>

                    <!-- Message Content -->
                    <div class="mt-3 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                        @if($isMainMessage)
                            <p class="text-lg font-semibold text-gray-900 dark:text-white break-words">
                                {{ __('filament-communicate::default.view.thread.subject_prefix') }} {{ $message->subject }}
                            </p>
                        @endif
                         <!-- Attachments - Versão com Ícones Filament -->
                    
                        <div class="prose prose-sm max-w-none dark:prose-invert">
                            {!! $message->content !!}
                        </div>
                       @if($message->attachments)
                        <!-- Status dentro da caixa de texto -->
                        <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                            <div class="flex items-start justify-between gap-4">
                                <!-- Anexos à esquerda -->
                                <div class="flex-1">
                                    @if($message->attachments)
                                        <div class="flex flex-wrap gap-2">
                                            @foreach($message->attachments as $attachmentUrl)
                                                @php
                                                    // Extrair informações do arquivo
                                                    $fileName = basename($attachmentUrl);
                                                    $extension = pathinfo($fileName, PATHINFO_EXTENSION);
                                                    $isImage = in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']);
                                                    $isPdf = strtolower($extension) === 'pdf';
                                                    $isDoc = in_array(strtolower($extension), ['doc', 'docx']);
                                                    $isExcel = in_array(strtolower($extension), ['xls', 'xlsx']);
                                                    
                                                    // Definir ícone baseado no tipo
                                                    if ($isImage) {
                                                        $icon = 'heroicon-m-photo';
                                                        $iconColor = 'text-green-600 dark:text-green-400';
                                                    } elseif ($isPdf) {
                                                        $icon = 'heroicon-m-document-text';
                                                        $iconColor = 'text-red-600 dark:text-red-400';
                                                    } elseif ($isDoc) {
                                                        $icon = 'heroicon-m-document';
                                                        $iconColor = 'text-blue-600 dark:text-blue-400';
                                                    } elseif ($isExcel) {
                                                        $icon = 'heroicon-m-table-cells';
                                                        $iconColor = 'text-green-600 dark:text-green-400';
                                                    } else {
                                                        $icon = 'heroicon-m-document';
                                                        $iconColor = 'text-gray-500 dark:text-gray-400';
                                                    }
                                                    
                                                    // Nome truncado para exibição
                                                    $shortName = strlen($fileName) > 15 ? substr($fileName, 0, 12) . '...' : $fileName;
                                                @endphp
                                                
                                                <a href="{{ Storage::url($attachmentUrl) }}" target="_blank" 
                                                   class="inline-flex items-center gap-1.5 px-2.5 py-1.5 text-xs bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-md border border-gray-200 dark:border-gray-600 transition-colors group" 
                                                   title="{{ $fileName }}">
                                                    
                                                    <!-- Ícone do tipo de arquivo -->
                                                    <x-filament::icon 
                                                        :icon="$icon" 
                                                        class="w-3 h-3 {{ $iconColor }}" 
                                                    />
                                                    
                                                    <span class="font-medium">{{ $shortName }}</span>
                                                    
                                                    <!-- Ícone de download -->
                                                    <x-filament::icon 
                                                        icon="heroicon-m-arrow-down-tray" 
                                                        class="w-3 h-3 text-gray-400 dark:text-gray-500 group-hover:text-gray-600 dark:group-hover:text-gray-300" 
                                                    />
                                                </a>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                        <div class="flex flex-row mt-2 items-center justify-between gap-4 text-xs">
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-gray-500 dark:text-gray-400"> 
                                    {{ $message->code }}
                                </span>
                                @if($message->read_at)
                                    <span class="text-xs text-gray-500 dark:text-gray-400"> 
                                        {{ __('filament-communicate::default.view.thread.read_at') }} {{ $message->read_at->format('d/m/Y H:i') }}
                                    </span>
                                @else
                                    <x-filament::badge color="gray" icon="heroicon-m-clock" size="sm">
                                        {{ __('filament-communicate::default.view.thread.not_read') }}
                                    </x-filament::badge>
                                @endif
                            </div>

                            <!-- Approval Status para mensagem principal -->
                            @if($isMainMessage)
                                @if($message->status->value === 'pending_approval')
                                    <x-filament::badge color="warning" icon="heroicon-m-clock" size="sm">
                                        {{ __('filament-communicate::default.view.thread.pending_approval') }}
                                    </x-filament::badge>
                                @endif
                            @endif
                        </div>
                </div>
            </div>
        </div>
        <!-- End Message Item -->
    @endforeach
    
    <!-- Informações de Trâmites -->
    @php
        $mainMessage = $messages->first();
        $transfers = $mainMessage->transfers ?? collect();
    @endphp
    
    <div class="mt-6" x-data="{ expanded: false }">
        <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
            <!-- Cabeçalho clicável -->
            <div class="p-4 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors" @click="expanded = !expanded">
                <div class="flex items-center justify-between">
                    <h4 class="text-sm font-medium text-gray-900 dark:text-white">{{ __('filament-communicate::default.view.thread.message_info') }}</h4>
                    <div class="flex items-center gap-2">
                        <!-- Indicador de status resumido -->
                        <div class="flex gap-1">
                            @if($mainMessage->approved_at)
                                <x-filament::badge color="success" size="sm" icon="heroicon-m-check-circle">
                                    {{ __('filament-communicate::default.view.thread.approved') }}
                                </x-filament::badge>
                            @elseif($mainMessage->rejected_at)
                                <x-filament::badge color="danger" size="sm" icon="heroicon-m-x-circle">
                                    {{ __('filament-communicate::default.view.thread.rejected') }}
                                </x-filament::badge>
                            @elseif($mainMessage->status->value === 'pending_approval')
                                <x-filament::badge color="warning" size="sm" icon="heroicon-m-clock">
                                    {{ __('filament-communicate::default.view.thread.pending') }}
                                </x-filament::badge>
                            @endif
                            
                            @if($transfers->isNotEmpty())
                                <x-filament::badge color="info" size="sm" icon="heroicon-m-arrow-right">
                                    {{ $transfers->count() }} {{ __('filament-communicate::default.view.thread.transfers_count') }}
                                </x-filament::badge>
                            @endif
                        </div>
                        
                        <!-- Ícone de expansão -->
                        <svg class="size-4 text-gray-400 transition-transform duration-200" :class="{ 'rotate-180': expanded }" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m6 9 6 6 6-6"/>
                        </svg>
                    </div>
                </div>
            </div>
            
            <!-- Conteúdo expansível -->
            <div x-show="expanded" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 max-h-0" x-transition:enter-end="opacity-100 max-h-screen" x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100 max-h-screen" x-transition:leave-end="opacity-0 max-h-0" class="border-t border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="p-4 space-y-2 text-sm">
                <!-- Criação -->
                <div class="flex justify-between items-center">
                    <span class="text-gray-600 dark:text-gray-400">
                        <svg class="inline size-4 mr-1" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                        {{ __('filament-communicate::default.view.thread.created_by') }} {{ $mainMessage->sender->name }}
                    </span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ $mainMessage->created_at->format('d/m/Y H:i') }}</span>
                </div>
                
                <!-- Destinatário original -->
                <div class="flex justify-between items-center">
                    <span class="text-gray-600 dark:text-gray-400">
                        <svg class="inline size-4 mr-1" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                        {{ __('filament-communicate::default.view.thread.recipient') }} {{ $mainMessage->recipient->name }}
                    </span>
                </div>
                
                <!-- Aprovação (se necessária) -->
                @if($mainMessage->messageType && $mainMessage->messageType->requires_approval)
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600 dark:text-gray-400">
                            <svg class="inline size-4 mr-1" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12l2 2 4-4"/><path d="M21 12c.552 0 1-.448 1-1V5a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v6c0 .552.448 1 1 1"/></svg>
                            @if($mainMessage->approved_at)
                                {{ __('filament-communicate::default.view.thread.approved_by') }} {{ $mainMessage->latestApproval?->approver?->name ?? 'Sistema' }}
                            @elseif($mainMessage->rejected_at)
                                {{ __('filament-communicate::default.view.thread.rejected_by') }} {{ $mainMessage->latestApproval?->approver?->name ?? 'Sistema' }}
                            @else
                                {{ __('filament-communicate::default.view.thread.awaiting_approval') }}
                            @endif
                        </span>
                        @if($mainMessage->approved_at)
                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $mainMessage->approved_at->format('d/m/Y H:i') }}</span>
                        @elseif($mainMessage->rejected_at)
                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $mainMessage->rejected_at->format('d/m/Y H:i') }}</span>
                        @endif
                    </div>
                @endif
                
                <!-- Transferências -->
                @if($transfers->isNotEmpty())
                    @foreach($transfers->sortBy('created_at') as $transfer)
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600 dark:text-gray-400">
                                <svg class="inline size-4 mr-1" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                                {{ __('filament-communicate::default.view.thread.transferred_from_to') }} {{ $transfer->fromUser->name }} {{ __('filament-communicate::default.view.thread.to') }} {{ $transfer->toUser->name }}
                                @if($transfer->reason)
                                    <br><span class="text-xs ml-5">{{ __('filament-communicate::default.view.thread.reason_label') }} {{ $transfer->reason }}</span>
                                @endif
                            </span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $transfer->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                    @endforeach
                @endif
                
                <!-- Responsável atual (se diferente do original) -->
                @if($mainMessage->currentRecipient && $mainMessage->currentRecipient->id !== $mainMessage->recipient->id)
                    <div class="flex justify-between items-center border-t border-gray-200 dark:border-gray-700 pt-2 mt-2">
                        <span class="text-gray-900 dark:text-white font-medium">
                            <svg class="inline size-4 mr-1" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="m22 21-3-3m0 0-3-3m3 3 3-3m-3 3-3 3"/></svg>
                            {{ __('filament-communicate::default.view.thread.current_responsible') }} {{ $mainMessage->currentRecipient->name }}
                        </span>
                        <x-filament::badge color="success" size="sm">
                            {{ __('filament-communicate::default.view.thread.current') }}
                        </x-filament::badge>
                    </div>
                @endif
                
                    <!-- Respostas -->
                    @if($messages->count() > 1)
                        <div class="flex justify-between items-center border-t border-gray-200 dark:border-gray-700 pt-2 mt-2">
                            <span class="text-gray-600 dark:text-gray-400">
                                <svg class="inline size-4 mr-1" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                                {{ $messages->count() - 1 }} {{ __('filament-communicate::default.view.thread.replies_in_thread') }}
                            </span>
                        </div>
                    @endif
                </div>
            </div>
            
            <!-- Rodapé sempre visível -->
            <div class="px-4 py-2 bg-gray-50 dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                    <span>{{ __('filament-communicate::default.view.thread.current_responsible_footer') }} {{ $mainMessage->currentRecipient?->name ?? $mainMessage->recipient->name }}</span>
                    <span>{{ __('filament-communicate::default.view.thread.created_at_footer') }} {{ $mainMessage->created_at->format('d/m/Y H:i') }}</span>
                </div>
            </div>
        </div>
    </div>
</div>