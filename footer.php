</main>

    <!-- Elegant Contact & Enquiries Panel -->
    <section class="border-t border-slate-200 bg-[#F5F2EB]/50 py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Full Width Contact Banner -->
            <div class="bg-indigo-950 text-white rounded-3xl p-8 sm:p-10 relative overflow-hidden shadow-md">
                <div class="relative z-10 grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
                    
                    <!-- Left side: Text brand header (5 cols) -->
                    <div class="lg:col-span-5 space-y-4">
                        <span class="inline-flex items-center space-x-1 rounded-full bg-white/10 px-3 py-1 text-[10px] font-bold uppercase tracking-widest text-indigo-300">
                            Connect With Us
                        </span>
                        <h3 class="text-2xl sm:text-3xl font-serif font-bold italic tracking-tight text-white">
                            Get in Touch
                        </h3>
                        <p class="text-indigo-200 text-xs leading-relaxed opacity-90 max-w-md">
                            Have questions about our custom volume collections, physical shelf inventory, or membership tiers? Drop us a line!
                        </p>
                        
                        <!-- Operating hours -->
                        <div class="pt-4 border-t border-white/10 flex items-center gap-3">
                            <div>
                                <p class="text-[9px] font-extrabold uppercase tracking-wider text-indigo-300">Lending Hours</p>
                                <p class="text-xs font-bold text-white mt-0.5">Fri - Thu: 9:00 AM - 5:00 PM</p>
                            </div>
                            <?php
                            date_default_timezone_set('Asia/Dhaka');
                            $hrs = (int)date('H');
                            $is_lib_open = ($hrs >= 9 && $hrs < 17);
                            if ($is_lib_open):
                            ?>
                                <span class="inline-flex items-center gap-1.5 bg-emerald-500/10 px-2.5 py-1 rounded-full border border-emerald-500/20">
                                    <span class="h-2 w-2 rounded-full bg-emerald-400 animate-pulse" title="Open Now"></span>
                                    <span class="text-[9px] font-extrabold uppercase tracking-wider text-emerald-400">Open Now</span>
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center gap-1.5 bg-rose-500/10 px-2.5 py-1 rounded-full border border-rose-500/20">
                                    <span class="h-2 w-2 rounded-full bg-rose-400 animate-pulse" title="Closed Now"></span>
                                    <span class="text-[9px] font-extrabold uppercase tracking-wider text-rose-400">Closed Now</span>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Right side: Contact Cards Grid (7 cols) -->
                    <div class="lg:col-span-7 grid grid-cols-1 sm:grid-cols-2 gap-6">
                        
                        <!-- Col 1 of details: Phone & Email -->
                        <div class="space-y-4">
                            <!-- Phone -->
                            <div class="flex items-start gap-4 p-4 rounded-2xl bg-white/5 border border-white/10 hover:bg-white/10 transition-all duration-150">
                                <div class="w-10 h-10 shrink-0 bg-white/10 rounded-xl flex items-center justify-center text-indigo-300">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.94.725l.548 2.2a1 1 0 01-.321.988l-1.305.98a10.582 10.582 0 004.872 4.872l.98-1.305a1 1 0 01.988-.321l2.2.548a1 1 0 01.725.94V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-[10px] font-extrabold uppercase tracking-widest text-indigo-400">Library Hotlines</h4>
                                    <p class="text-xs font-semibold text-slate-100 mt-0.5 leading-normal">+880 1712-345678<br>+880 1987-654321</p>
                                </div>
                            </div>

                            <!-- Email -->
                            <div class="flex items-start gap-4 p-4 rounded-2xl bg-white/5 border border-white/10 hover:bg-white/10 transition-all duration-150">
                                <div class="w-10 h-10 shrink-0 bg-white/10 rounded-xl flex items-center justify-center text-indigo-300">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-[10px] font-extrabold uppercase tracking-widest text-indigo-400">Electronic Support</h4>
                                    <p class="text-xs font-semibold text-slate-100 mt-0.5 break-all">library@seu.edu.bd</p>
                                </div>
                            </div>
                        </div>

                        <!-- Col 2 of details: Address Location -->
                        <div class="flex items-start gap-4 p-4 rounded-2xl bg-white/5 border border-white/10 hover:bg-white/10 transition-all duration-150 h-full">
                            <div class="w-10 h-10 shrink-0 bg-white/10 rounded-xl flex items-center justify-center text-indigo-300">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </div>
                            <div>
                                <h4 class="text-[10px] font-extrabold uppercase tracking-widest text-indigo-400">Main Office</h4>
                                <p class="text-xs font-semibold text-slate-100 mt-0.5 leading-relaxed">
                                    Southeast University Library Central Wing,<br>tejgoan, Dhaka-1213, Bangladesh.
                                </p>
                            </div>
                        </div>

                    </div>

                </div>

                <!-- Decorative blur element -->
                <div class="absolute -right-16 -bottom-16 w-48 h-48 bg-indigo-700/30 rounded-full blur-3xl opacity-50"></div>
            </div>
        </div>
    </section>

    <!-- Beautiful rich multi-column footer -->
    <footer class="border-t border-slate-200 bg-white pt-12 pb-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-8 pb-10 text-left items-start">
                
                <!-- Col 1: Brand & motto (7 cols) -->
                <div class="md:col-span-7 space-y-4">
                    <div class="flex items-center space-x-2.5">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-600 text-white shadow-md">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                            </svg>
                        </div>
                        <span class="text-xl font-serif font-bold tracking-tight text-indigo-950">
                            BookClub <span class="italic text-indigo-600 font-serif">Portal</span>
                        </span>
                    </div>
                    <p class="text-slate-500 text-xs leading-relaxed max-w-md">
                        A modern, high-contrast digital sanctuary built for physical library catalogs and active member rooms. Managed securely under standard PHP .
                    </p>
                </div>

                <!-- Col 2: Navigation Links (5 cols) -->
                <div class="md:col-span-5 space-y-3.5 md:text-right">
                    <h5 class="text-[10px] font-extrabold uppercase tracking-widest text-slate-400">Main Directories</h5>
                    <ul class="space-y-2.5 text-xs font-semibold text-slate-650 flex flex-col md:items-end">
                        <li><a href="index.php" class="hover:text-indigo-600 transition-colors flex items-center gap-1.5 font-medium"><span>Global Dashboard</span> ➔</a></li>
                        <li><a href="catalog.php" class="hover:text-indigo-600 transition-colors flex items-center gap-1.5 font-medium"><span>Explore Volumes</span> ➔</a></li>
                        <li><a href="members.php" class="hover:text-indigo-600 transition-colors flex items-center gap-1.5 font-medium"><span>Academic Directory</span> ➔</a></li>
                        <?php if (isset($user_id) && $user_id): ?>
                            <li><a href="my-list.php" class="hover:text-indigo-600 transition-colors flex items-center gap-1.5 font-medium"><span>Personal Bookshelf</span> ➔</a></li>
                        <?php endif; ?>
                    </ul>
                </div>

            </div>

            <!-- Bottom copyrighted legal bar -->
            <div class="border-t border-slate-100 pt-6 flex flex-col items-center justify-center text-center text-[11px] text-slate-400 font-medium space-y-2">
                <span>&copy; <?php echo date('Y'); ?> BookClub Portal. Powered by <b>Mahidul Islam Rabbi.</b> All rights reserved.</span>
            </div>
        </div>
    </footer>

</body>
</html>
