@php
    use Filament\Support\Enums\IconSize;
    use Illuminate\View\ComponentAttributeBag;
    use function Filament\Support\generate_icon_html;

    $hasDocumentation = mekaya()->documentationEnabled();
    $showAuthFooter = filament()->auth()->check()
        && ($hasDatabaseNotificationsInSidebar || $hasUserMenuInSidebar);

    $user = auth()->user();
    $isSuperAdmin = $user?->hasRole('super_admin') ?? false;

    $showHorizon = $user && (
        $isSuperAdmin
        || $user->can('ViewHorizon')
        || $user->can('view_horizon')
        || $user->can('viewHorizon')
        || $user->can('View:Horizon')
    );

    $showLogViewer = $user && (
        $isSuperAdmin
        || $user->can('ViewLogViewer')
        || $user->can('view_log_viewer')
        || $user->can('viewLogViewer')
        || $user->can('View:LogViewer')
    );

    $horizonUrl = '/' . ltrim((string) config('horizon.path', 'horizon'), '/');
    $logViewerUrl = '/' . ltrim((string) config('log-viewer.route_path', 'log-viewer'), '/');
@endphp

@if ($hasDocumentation || $showAuthFooter || $showHorizon || $showLogViewer)
    <div class="mky-sidebar border-t border-gray-200 px-3 pt-3 pb-6 dark:border-white/20">
        @if ($showHorizon || $showLogViewer)
            <div class="mky-sidebar-group">
                <ul role="list" class="mky-sidebar-group-items">
                    @if ($showHorizon)
                        <li class="mky-sidebar-item">
                            <a
                                href="{{ $horizonUrl }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="mky-sidebar-item-link"
                                x-tooltip="{
                                    content: @js(__('Horizon')),
                                    placement: document.dir === 'rtl' ? 'left' : 'right',
                                    theme: $store.theme,
                                    onShow: () => $store.sidebar.isCollapsed,
                                }"
                            >
                                {{
                                    generate_icon_html(
                                        'heroicon-o-cpu-chip',
                                        attributes: (new ComponentAttributeBag)->class(['mky-sidebar-item-icon']),
                                        size: IconSize::Large,
                                    )
                                }}

                                <span
                                    class="mky-sidebar-item-label"
                                    x-cloak
                                    x-show="! $store.sidebar.isCollapsed"
                                    x-transition:enter="transition-opacity duration-200"
                                    x-transition:enter-start="opacity-0"
                                    x-transition:enter-end="opacity-100"
                                >
                                    {{ __('Horizon') }}
                                </span>
                            </a>
                        </li>
                    @endif

                    @if ($showLogViewer)
                        <li class="mky-sidebar-item">
                            <a
                                href="{{ $logViewerUrl }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="mky-sidebar-item-link"
                                x-tooltip="{
                                    content: @js(__('Log Viewer')),
                                    placement: document.dir === 'rtl' ? 'left' : 'right',
                                    theme: $store.theme,
                                    onShow: () => $store.sidebar.isCollapsed,
                                }"
                            >
                                {{
                                    generate_icon_html(
                                        'heroicon-o-document-text',
                                        attributes: (new ComponentAttributeBag)->class(['mky-sidebar-item-icon']),
                                        size: IconSize::Large,
                                    )
                                }}

                                <span
                                    class="mky-sidebar-item-label"
                                    x-cloak
                                    x-show="! $store.sidebar.isCollapsed"
                                    x-transition:enter="transition-opacity duration-200"
                                    x-transition:enter-start="opacity-0"
                                    x-transition:enter-end="opacity-100"
                                >
                                    {{ __('Log Viewer') }}
                                </span>
                            </a>
                        </li>
                    @endif
                </ul>
            </div>
        @endif

        @if ($hasDocumentation)
            @include('mekaya::livewire.partials.mekaya-sidebar-documentation')
        @endif

        @if ($showAuthFooter)
            @if ($hasDatabaseNotificationsInSidebar && ($dbNotificationsComponent = mekaya_database_notifications_component()))
                @livewire($dbNotificationsComponent, [
                    'lazy' => mekaya_database_notifications_is_lazy(),
                ])
            @endif

            @if ($hasUserMenuInSidebar)
                <x-filament-panels::user-menu />
            @endif
        @endif
    </div>
@endif
