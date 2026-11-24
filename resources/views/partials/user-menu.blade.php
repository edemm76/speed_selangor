{{--
    Logged-in user menu (top right): name + "Profil Saya" + "Log Keluar".
    Used by partials/navbar for both roles.
    Needs: $name, $subtitle, $icon, $profileUrl, $profileActive
--}}
<div class="dropdown mt-3 mt-lg-0">
    <button class="btn ss-user-btn dropdown-toggle {{ $profileActive ? 'active' : '' }}" type="button"
            data-bs-toggle="dropdown" aria-expanded="false">
        <i class="bi {{ $icon }} fs-5"></i>
        {{-- Name is hidden on medium screens (992–1199px) so the menu fits on one line --}}
        <span class="text-start d-lg-none d-xl-inline">
            {{ $name }}<br><small>{{ $subtitle }}</small>
        </span>
    </button>
    <ul class="dropdown-menu dropdown-menu-end shadow-sm">
        <li>
            <a class="dropdown-item" href="{{ $profileUrl }}">
                <i class="bi bi-person-gear"></i> Profil Saya
            </a>
        </li>
        <li>
            <a class="dropdown-item" href="{{ $profileUrl }}#tukar-kata-laluan">
                <i class="bi bi-key"></i> Tukar Kata Laluan
            </a>
        </li>
        <li><hr class="dropdown-divider"></li>
        <li>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="dropdown-item text-danger">
                    <i class="bi bi-box-arrow-right"></i> Log Keluar
                </button>
            </form>
        </li>
    </ul>
</div>