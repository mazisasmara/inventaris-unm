
<x-layouts::auth :title="__('Register')">

    <div class="grid min-h-dvh w-full grid-cols-1 bg-white text-zinc-900 transition-colors dark:bg-zinc-950 dark:text-white lg:grid-cols-[44.2%_55.8%]">

        {{-- ==========================================
             PANEL KIRI: FOTO GEDUNG UNM
        =========================================== --}}
        <section
            class="relative h-[230px] overflow-hidden bg-cover bg-center bg-no-repeat sm:h-[300px] lg:sticky lg:top-0 lg:h-dvh"
            style="background-image: url('{{ asset('images/unmBerwarna.jpeg') }}')"
        >

            <div class="absolute inset-0 bg-gradient-to-t from-[#06284b] via-[#06284b]/30 to-transparent"></div>

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
             PANEL KANAN: FORM REGISTER
        =========================================== --}}
        <section class="flex min-h-dvh flex-col bg-white px-6 py-10 transition-colors dark:bg-zinc-950 sm:px-12 lg:px-12 xl:px-20">

            <div class="mx-auto flex w-full max-w-[470px] flex-1 flex-col justify-center">

                {{-- LOGO UNM --}}
                <div class="mb-10 border-b-2 border-zinc-900 pb-3 dark:border-zinc-600 sm:mb-12">

                    <img
                        src="{{ asset('images/logo.jpeg') }}"
                        alt="Universitas Negeri Makassar"
                        class="mx-auto h-auto max-h-[100px] w-full max-w-[440px] object-contain"
                    >

                </div>


                {{-- JUDUL --}}
                <div class="mb-7">

                    <h2 class="text-2xl font-bold tracking-tight text-zinc-950 dark:text-white sm:text-3xl">
                        Buat Akun Anda
                    </h2>

                    <p class="mt-1 text-sm font-semibold text-zinc-500 dark:text-zinc-400">
                        Silakan lengkapi data berikut untuk membuat akun
                    </p>

                </div>


                {{-- SESSION STATUS --}}
                <x-auth-session-status
                    class="mb-4 text-center"
                    :status="session('status')"
                />


                {{-- FORM REGISTER --}}
                <form
                    method="POST"
                    action="{{ route('register.store') }}"
                    class="flex flex-col gap-4"
                >
                    @csrf

                    {{-- NAMA --}}
                    <div>
                        <label for="name" class="mb-1.5 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            Nama Lengkap
                        </label>

                        <input
                            id="name"
                            type="text"
                            name="name"
                            value="{{ old('name') }}"
                            required
                            autofocus
                            autocomplete="name"
                            placeholder="Masukkan nama lengkap"
                            class="h-[43px] w-full rounded-lg border-0 bg-[#dedede] px-3 text-sm text-zinc-900 placeholder:text-zinc-600 transition focus:bg-[#e5e5e5] focus:outline-none focus:ring-2 focus:ring-[#4055ed]/40 dark:bg-zinc-800 dark:text-white dark:placeholder:text-zinc-400 dark:focus:bg-zinc-700"
                        >

                        @error('name')
                            <p class="mt-1.5 text-xs text-red-600 dark:text-red-400">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>


                    {{-- EMAIL --}}
                    <div>
                        <label for="email" class="mb-1.5 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            Email
                        </label>

                        <input
                            id="email"
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            required
                            autocomplete="email"
                            placeholder="email@example.com"
                            class="h-[43px] w-full rounded-lg border-0 bg-[#dedede] px-3 text-sm text-zinc-900 placeholder:text-zinc-600 transition focus:bg-[#e5e5e5] focus:outline-none focus:ring-2 focus:ring-[#4055ed]/40 dark:bg-zinc-800 dark:text-white dark:placeholder:text-zinc-400 dark:focus:bg-zinc-700"
                        >

                        @error('email')
                            <p class="mt-1.5 text-xs text-red-600 dark:text-red-400">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>


                    {{-- NIM / NIP --}}
                    <div>
                        <label for="identity_number" class="mb-1.5 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            NIM / NIP
                        </label>

                        <input
                            id="identity_number"
                            type="text"
                            name="identity_number"
                            value="{{ old('identity_number') }}"
                            required
                            autocomplete="off"
                            placeholder="Masukkan NIM atau NIP"
                            class="h-[43px] w-full rounded-lg border-0 bg-[#dedede] px-3 text-sm text-zinc-900 placeholder:text-zinc-600 transition focus:bg-[#e5e5e5] focus:outline-none focus:ring-2 focus:ring-[#4055ed]/40 dark:bg-zinc-800 dark:text-white dark:placeholder:text-zinc-400 dark:focus:bg-zinc-700"
                        >

                        @error('identity_number')
                            <p class="mt-1.5 text-xs text-red-600 dark:text-red-400">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>


                    {{-- PASSWORD --}}
                    <div>
                        <label for="password" class="mb-1.5 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            Password
                        </label>

                        <input
                            id="password"
                            type="password"
                            name="password"
                            required
                            autocomplete="new-password"
                            placeholder="Masukkan password"
                            class="h-[43px] w-full rounded-lg border-0 bg-[#dedede] px-3 text-sm text-zinc-900 placeholder:text-zinc-600 transition focus:bg-[#e5e5e5] focus:outline-none focus:ring-2 focus:ring-[#4055ed]/40 dark:bg-zinc-800 dark:text-white dark:placeholder:text-zinc-400 dark:focus:bg-zinc-700"
                        >

                        @error('password')
                            <p class="mt-1.5 text-xs text-red-600 dark:text-red-400">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>


                    {{-- KONFIRMASI PASSWORD --}}
                    <div>
                        <label for="password_confirmation" class="mb-1.5 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            Konfirmasi Password
                        </label>

                        <input
                            id="password_confirmation"
                            type="password"
                            name="password_confirmation"
                            required
                            autocomplete="new-password"
                            placeholder="Masukkan ulang password"
                            class="h-[43px] w-full rounded-lg border-0 bg-[#dedede] px-3 text-sm text-zinc-900 placeholder:text-zinc-600 transition focus:bg-[#e5e5e5] focus:outline-none focus:ring-2 focus:ring-[#4055ed]/40 dark:bg-zinc-800 dark:text-white dark:placeholder:text-zinc-400 dark:focus:bg-zinc-700"
                        >
                    </div>


                    {{-- TOMBOL REGISTER --}}
                    <div class="flex justify-center pt-2">

                        <button
                            type="submit"
                            data-test="register-user-button"
                            class="h-[43px] w-full max-w-[238px] rounded-lg bg-[#4055f5] text-sm font-semibold text-white transition duration-200 hover:bg-[#3045df] focus:outline-none focus:ring-4 focus:ring-[#4055f5]/20 active:scale-[0.98]"
                        >
                            Buat Akun
                        </button>

                    </div>

                </form>


                {{-- SUDAH PUNYA AKUN --}}
                <div class="mt-5 text-center text-sm text-zinc-900 dark:text-zinc-300">

                    <span>Sudah punya akun?</span>

                    <a
                        href="{{ route('login') }}"
                        wire:navigate
                        class="font-medium text-[#4055ed] transition hover:underline dark:text-blue-400"
                    >
                        Masuk
                    </a>

                </div>


                {{-- HUBUNGI ADMIN --}}
                <div class="mt-5 border-t border-zinc-200 pt-5 text-center dark:border-zinc-800">

                    <p class="text-sm text-zinc-600 dark:text-zinc-400">
                        Memiliki kendala saat membuat akun?
                    </p>

                    <a
                        href="{{ config('app.admin_contact_url', '#') }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="mt-2 inline-flex items-center justify-center gap-2 text-sm font-semibold text-[#4055ed] transition hover:text-[#2638bd] hover:underline dark:text-blue-400 dark:hover:text-blue-300"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 24 24"
                            fill="currentColor"
                            class="size-4"
                        >
                            <path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.46 1.34 4.97L2 22l5.26-1.38c1.46.8 3.1 1.22 4.78 1.22h.01c5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.84 9.84 0 0 0 12.04 2Zm0 18.16h-.01a8.23 8.23 0 0 1-4.19-1.15l-.3-.18-3.12.82.83-3.04-.2-.31a8.2 8.2 0 0 1-1.26-4.39c0-4.55 3.7-8.25 8.26-8.25 2.2 0 4.28.86 5.84 2.42a8.2 8.2 0 0 1 2.42 5.84c0 4.55-3.71 8.24-8.27 8.24Zm4.53-6.18c-.25-.13-1.47-.73-1.7-.81-.23-.08-.4-.13-.57.13-.17.25-.65.81-.8.97-.15.17-.3.19-.55.06-.25-.13-1.06-.39-2.02-1.24-.75-.67-1.25-1.5-1.4-1.75-.15-.25-.02-.39.11-.52.11-.11.25-.3.38-.45.12-.15.17-.25.25-.42.08-.17.04-.32-.02-.45-.06-.13-.57-1.37-.78-1.88-.2-.49-.41-.42-.57-.43h-.49c-.17 0-.45.06-.68.32-.23.25-.89.87-.89 2.12s.91 2.46 1.04 2.63c.13.17 1.79 2.73 4.33 3.83.61.26 1.08.42 1.45.54.61.19 1.17.16 1.61.1.49-.07 1.47-.6 1.68-1.18.21-.58.21-1.08.15-1.18-.06-.1-.23-.16-.49-.29Z"/>
                        </svg>

                        Hubungi Admin Kampus
                    </a>

                </div>

            </div>


            {{-- COPYRIGHT --}}
            <footer class="mx-auto mt-10 flex min-h-[88px] w-full max-w-[530px] items-center justify-center rounded-2xl bg-[#f4f4f4] px-4 py-5 text-center transition-colors dark:bg-zinc-900">

                <p class="text-sm text-[#536987] dark:text-zinc-400">
                    @copyright {{ date('Y') }} Universitas Negeri Makassar
                </p>

            </footer>

        </section>

    </div>

</x-layouts::auth>
