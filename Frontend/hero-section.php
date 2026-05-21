<section class="relative overflow-hidden bg-gradient-to-b from-background to-card">
    <!-- Background Pattern -->
    <div class="absolute inset-0 opacity-5">
        <div class="absolute left-1/4 top-1/4 h-96 w-96 rounded-full bg-primary blur-3xl"></div>
        <div class="absolute bottom-1/4 right-1/4 h-96 w-96 rounded-full bg-primary blur-3xl"></div>
    </div>

    <div class="container relative mx-auto px-4 py-20 lg:py-32">
        <div class="grid items-center gap-12 lg:grid-cols-2">
            <!-- Content -->
            <div class="space-y-8">
                <div class="inline-flex items-center gap-2 rounded-full border border-primary/30 bg-primary/10 px-4 py-2 text-sm text-primary">
                    <svg class="h-4 w-4 fill-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                    </svg>
                    <span>Sistema de reputación por género</span>
                </div>

                <h1 class="text-balance text-4xl font-bold leading-tight text-foreground md:text-5xl lg:text-6xl">
                    Conecta con el cine a través de <span class="text-primary">reseñas confiables</span>
                </h1>

                <p class="max-w-xl text-pretty text-lg text-muted-foreground">
                    Descubre películas basándote en opiniones de usuarios especializados en cada género. 
                    Comparte tus reseñas y construye tu reputación como crítico de cine.
                </p>

                <div class="flex flex-col gap-4 sm:flex-row">
                    <button class="bg-primary text-primary-foreground hover:bg-primary/90 px-6 py-3 rounded-lg text-lg font-medium flex items-center gap-2">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1.586a1 1 0 01.707.293l.707.707A1 1 0 0012.414 11H13m-3 3a1 1 0 100-2 1 1 0 000 2z"></path>
                        </svg>
                        Comenzar Ahora
                    </button>
                    <button class="border border-border text-foreground hover:bg-secondary px-6 py-3 rounded-lg text-lg font-medium">
                        Explorar Películas
                    </button>
                </div>

                <!-- Stats -->
                <div class="flex flex-wrap gap-8 pt-8">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <svg class="h-5 w-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                            </svg>
                            <span class="text-2xl font-bold text-foreground">50K+</span>
                        </div>
                        <p class="text-sm text-muted-foreground">Usuarios Activos</p>
                    </div>
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <svg class="h-5 w-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                            </svg>
                            <span class="text-2xl font-bold text-foreground">200K+</span>
                        </div>
                        <p class="text-sm text-muted-foreground">Reseñas</p>
                    </div>
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <svg class="h-5 w-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                            </svg>
                            <span class="text-2xl font-bold text-foreground">15+</span>
                        </div>
                        <p class="text-sm text-muted-foreground">Géneros</p>
                    </div>
                </div>
            </div>

            <!-- Hero Image / Movie Cards -->
            <div class="relative hidden lg:block">
                <div class="relative mx-auto w-full max-w-md">
                    <!-- Main Card -->
                    <div class="relative z-20 overflow-hidden rounded-2xl border border-border bg-card shadow-2xl">
                        <div class="aspect-[2/3] bg-gradient-to-br from-secondary to-card">
                            <div class="flex h-full flex-col items-center justify-center p-6 text-center">
                                <div class="mb-4 flex h-20 w-20 items-center justify-center rounded-full bg-primary/20">
                                    <svg class="h-10 w-10 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1.586a1 1 0 01.707.293l.707.707A1 1 0 0012.414 11H13m-3 3a1 1 0 100-2 1 1 0 000 2z"></path>
                                    </svg>
                                </div>
                                <p class="text-sm text-muted-foreground">Película Destacada</p>
                                <h3 class="mt-2 text-xl font-bold text-foreground">Dune: Parte Dos</h3>
                                <div class="mt-3 flex items-center gap-1">
                                    <svg class="h-4 w-4 fill-primary text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                                    </svg>
                                    <svg class="h-4 w-4 fill-primary text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                                    </svg>
                                    <svg class="h-4 w-4 fill-primary text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                                    </svg>
                                    <svg class="h-4 w-4 fill-primary text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                                    </svg>
                                    <svg class="h-4 w-4 fill-primary text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                                    </svg>
                                    <span class="ml-2 text-sm text-muted-foreground">4.8</span>
                                </div>
                            </div>
                        </div>
                        <div class="p-4">
                            <div class="flex items-center justify-between">
                                <span class="rounded-full bg-primary/10 px-3 py-1 text-xs text-primary">Ciencia Ficción</span>
                                <span class="text-sm text-muted-foreground">2024</span>
                            </div>
                        </div>
                    </div>

                    <!-- Background Cards -->
                    <div class="absolute -left-8 top-8 z-10 h-[85%] w-full -rotate-6 rounded-2xl border border-border bg-secondary/50"></div>
                    <div class="absolute -right-8 top-16 z-0 h-[75%] w-full rotate-6 rounded-2xl border border-border bg-secondary/30"></div>
                </div>
            </div>
        </div>
    </div>
</section>
