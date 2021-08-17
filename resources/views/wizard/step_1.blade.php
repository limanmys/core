@extends("wizard.master")

@section("content")
                    <h1 class="text-center text-2xl font-bold mb-10">
                        <span id="welcome-text">

                        </span>
                    </h1>

                    <div class="language-selector">
                        <div class="mt-1 w-full z-10 rounded-md bg-white shadow-lg mb-8">
                            <ul tabindex="-1" role="listbox" aria-labelledby="listbox-label"
                                aria-activedescendant="listbox-item-3"
                                class="max-h-56 rounded-md py-1 text-base ring-1 ring-black ring-opacity-5 overflow-auto focus:outline-none sm:text-sm">
                                <li id="listbox-item-0" role="option"
                                    @if ($lang != "tr") onclick="setLang('tr')" @endif
                                    class="text-gray-900 cursor-default hover:bg-indigo-500 hover:text-white select-none relative py-2 pl-3 pr-9">
                                    <div class="flex items-center">
                                        <span class="ml-3 block font-normal truncate">
                                            Türkçe
                                        </span>
                                    </div>
                                    @if ($lang == "tr")
                                    <span class="absolute inset-y-0 right-0 flex items-center pr-4">
                                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                            fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd"
                                                d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                clip-rule="evenodd">
                                            </path>
                                        </svg>
                                    </span>
                                    @endif
                                </li>
                                <li id="listbox-item-1" role="option"
                                    @if ($lang != "en") onclick="setLang('en')" @endif
                                    class="text-gray-900 cursor-default select-none hover:bg-indigo-500 hover:text-white relative py-2 pl-3 pr-9">
                                    <div class="flex items-center">
                                        <span class="ml-3 block font-normal truncate">
                                            English
                                        </span>
                                    </div>
                                    @if ($lang == "en")
                                    <span class="absolute inset-y-0 right-0 flex items-center pr-4">
                                        <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                            fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd"
                                                d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                clip-rule="evenodd">
                                            </path>
                                        </svg>
                                    </span>
                                    @endif
                                </li>
                            </ul>
                        </div>
                    </div>
                    <p class="text-center text-xs text-gray-400">Sistem dilini seçiniz.</p>
                    <p class="text-center text-xs text-gray-400 mb-20">Choose system language.</p>

                    <script>
                        function setLang(locale) {
                            let data = new FormData();
                            data.append("locale", locale)
                            request("{{ route('save_wizard', 1) }}", data, function (response) {
                                location.reload();
                            }, function (error) {
                                // not expecting errors
                            });
                        }
                    </script>
@endsection