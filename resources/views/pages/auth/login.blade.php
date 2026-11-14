
<x-layouts::auth :title="__('Log in')">

    <div class="grid min-h-dvh w-full grid-cols-1 bg-white text-zinc-900 transition-colors dark:bg-zinc-950 dark:text-white lg:grid-cols-[44.2%_55.8%]">

        {{-- ==========================================
             PANEL KIRI: FOTO GEDUNG UNM
        =========================================== --}}
        <section
            class="relative h-[230px] overflow-hidden bg-cover bg-center bg-no-repeat sm:h-[300px] lg:sticky lg:top-0 lg:h-dvh"
            style="background-image: url('{{ asset('images/unmBerwarna.jpeg') }}')"
        >

            {{-- Overlay gradasi --}}
            <div class="absolute inset-0 bg-gradient-to-t from-[#06284b] via-[#06284b]/30 to-transparent"></div>

            {{-- Informasi UNM --}}
            <div class="absolute bottom-0 left-0 z-10 w-full px-7 pb-7 text-white sm:px-10 sm:pb-10 lg:px-11 lg:pb-14">

                <h1 class="mb-1 text-3xl font-bold tracking-tight sm:text-4xl">
                    UNM
                </h1>

                <p class="max-w-md text-sm leading-5 text-white/90 sm:text-base sm:leading-6 lg:text-lg">
                    Selamat datang di website<br>
                    inventaris/peminjaman barang dan lab
                </p>

                <div class="mt-4 h-[2px] w-[102px] bg-yellow-400 sm:mt-5"></div>

            </div>
        </section>


        {{-- ==========================================
             PANEL KANAN: FORM LOGIN
        =========================================== --}}
        <section class="flex min-h-dvh flex-col bg-white px-6 py-10 transition-colors dark:bg-zinc-950 sm:px-12 lg:px-12 xl:px-20">

            {{-- Konten utama --}}
            <div class="mx-auto flex w-full max-w-[470px] flex-1 flex-col justify-center">

                {{-- LOGO UNM --}}
                <div class="mb-12 border-b-2 border-zinc-900 pb-3 dark:border-zinc-600 sm:mb-16 lg:mb-20">

                    <img
                        src="{{ asset('images/logo.jpeg') }}"
                        alt="Universitas Negeri Makassar"
                        class="mx-auto h-auto max-h-[115px] w-full max-w-[440px] object-contain"
                    >

                </div>


                {{-- JUDUL --}}
                <div class="mb-7">

                    <h2 class="text-2xl font-bold tracking-tight text-zinc-950 dark:text-white sm:text-3xl">
                        Masuk ke Akun Anda
                    </h2>

                    <p class="mt-1 text-sm font-semibold text-zinc-500 dark:text-zinc-400">
                        Silakan login untuk melanjutkan
                    </p>

                </div>


                {{-- STATUS LOGIN --}}
                <x-auth-session-status
                    class="mb-4 text-center"
                    :status="session('status')"
                />


                {{-- FORM LOGIN --}}
                <form
                    method="POST"
                    action="{{ route('login.store') }}"
                    class="flex flex-col gap-6"
                >
                    @csrf

                    {{-- Email --}}
                    <div>

                        <input
                            type="text"
                            name="email"
                            value="{{ old('email') }}"
                            required
                            autofocus
                            placeholder="Email"
                            class="h-[43px] w-full rounded-lg border-0 bg-[#dedede] px-3 text-sm text-zinc-900 placeholder:text-zinc-600 transition focus:bg-[#e5e5e5] focus:outline-none focus:ring-2 focus:ring-[#4055ed]/40 dark:bg-zinc-800 dark:text-white dark:placeholder:text-zinc-400 dark:focus:bg-zinc-700"
                        >

                        @error('email')
                            <p class="mt-1.5 text-xs text-red-600 dark:text-red-400">
                                {{ $message }}
                            </p>
                        @enderror

                    </div>


                    {{-- PASSWORD --}}
                    <div>

                        <input
                            type="password"
                            name="password"
                            required
                            autocomplete="current-password"
                            placeholder="Password"
                            class="h-[43px] w-full rounded-lg border-0 bg-[#dedede] px-3 text-sm text-zinc-900 placeholder:text-zinc-600 transition focus:bg-[#e5e5e5] focus:outline-none focus:ring-2 focus:ring-[#4055ed]/40 dark:bg-zinc-800 dark:text-white dark:placeholder:text-zinc-400 dark:focus:bg-zinc-700"
                        >

                        @error('password')
                            <p class="mt-1.5 text-xs text-red-600 dark:text-red-400">
                                {{ $message }}
                            </p>
                        @enderror

                    </div>


                    {{-- TOMBOL LOGIN --}}
                    <div class="flex justify-center">

                        <button
                            type="submit"
                            data-test="login-button"
                            class="h-[42px] w-full max-w-[238px] rounded-lg bg-[#4055f5] text-sm font-semibold text-white transition duration-200 hover:bg-[#3045df] focus:outline-none focus:ring-4 focus:ring-[#4055f5]/20 active:scale-[0.98]"
                        >
                            Login
                        </button>

                    </div>

                </form>


                {{-- HUBUNGI ADMIN --}}
                <div class="mt-5 text-center text-sm text-zinc-900 dark:text-zinc-300">
                    <span>Belum punya akun?</span>

                    <a
                        href="{{ route('register') }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="font-medium text-[#4055ed] transition hover:underline dark:text-blue-400"
                    >
                        Buat akun
                    </a>
                </div>

            </div>


            {{-- ======================================
                 COPYRIGHT
            ======================================= --}}
            <footer class="mx-auto mt-10 flex min-h-[88px] w-full max-w-[530px] items-center justify-center rounded-2xl bg-[#f4f4f4] px-4 py-5 text-center transition-colors dark:bg-zinc-900">

                <p class="text-sm text-[#536987] dark:text-zinc-400">
                    @copyright {{ date('Y') }} Universitas Negeri Makassar
                </p>

            </footer>

        </section>

    </div>

</x-layouts::auth>
