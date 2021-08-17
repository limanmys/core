@extends("wizard.master")

@section("content")
                    <div id="error" style="display: none;" class="bg-teal-100 mb-5 border-t-4 border-teal-500 rounded-b text-teal-900 px-4 py-3 shadow-md" role="alert">
                        <div class="flex">
                            <div class="py-1"><svg class="fill-current h-6 w-6 text-teal-500 mr-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M2.93 17.07A10 10 0 1 1 17.07 2.93 10 10 0 0 1 2.93 17.07zm12.73-1.41A8 8 0 1 0 4.34 4.34a8 8 0 0 0 11.32 11.32zM9 11V9h2v6H9v-4zm0-6h2v2H9V5z"/></svg></div>
                            <div>
                            <p id="errorTitle" class="font-bold"> </p>
                            <p id="errorMessage" class="text-sm"> </p>
                            </div>
                        </div>
                    </div>

                    <h1 class="text-2xl font-bold mb-10">
                        {{ __("Yeni kullanıcı oluştur") }}
                    </h1>

                    <div class="new-user">
                        <div class="grid grid-cols-2 gap-4">
                            <label class="block mb-4">
                                <span class="text-gray-700">{{ __("Ad soyad") }}</span>
                                <input id="name" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" placeholder="">
                            </label>
                            <label class="block mb-4">
                                <span class="text-gray-700">{{ __("Kullanıcı adı") }}</span>
                                <input id="username" type="text" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" placeholder="">
                            </label>
                        </div>
                        
                        <label class="block mb-4">
                            <span class="text-gray-700">{{ __("E-posta adresi") }}</span>
                            <input id="email" type="email" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" placeholder="">
                        </label>
                        <label class="block mb-4">
                            <span class="text-gray-700">{{ __("Şifre") }}</span>
                            <input id="password" type="password" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" placeholder="">
                            <p class="text-xs text-gray-400" style="margin-top:5px;">{{ __("Şifreniz 10-32 karakter arasında, en az bir büyük harf, bir sayı ve bir özel karakter içermelidir.") }}</p>
                        </label>
                        <label class="block mb-4">
                            <span class="text-gray-700">{{ __("Şifrenizi yeniden giriniz") }}</span>
                            <input id="confirm" type="password" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50" placeholder="">
                        </label>
                    </div>
                    <p class="text-center text-xs text-gray-400 mb-20">{{ __("Yeni bir sistem kullanıcısı oluşturun.") }}</p>

                    <script>
                        function createUser() {
                            let data = new FormData();
                            let isempty = false;
                            $(".new-user").find("input").each(function () {
                                if ($(this).val().length == 0) {
                                    $("#errorTitle").html("{{ __("Bilgilendirme") }}");
                                    $("#errorMessage").html("{{ __("Boşlukları doldurmadığınız için kullanıcı oluşturmadan ilerleniyor...") }}");
                                    $("#error").fadeIn(300);
                                    
                                    isempty = true;

                                    setTimeout(() => {
                                        location.href="{{ route('wizard', 3) }}";
                                    }, 2500);
                                }
                                data.append(this.id, $(this).val());
                            });
                            setTimeout(() => {
                                if (!isempty) {
                                    createUserRequest(data);
                                }
                            }, 100);
                        }

                        function createUserRequest(data) 
                        {
                            request("{{ route('save_wizard', 2) }}", data, function (response) {
                                $("#errorTitle").html("{{ __("Bilgilendirme") }}");
                                $("#errorMessage").html("{{ __("Kullanıcı başarıyla eklendi. Diğer adıma yönlendiriliyorsunuz...") }}");
                                $("#error").fadeIn(300);

                                setTimeout(() => {
                                    location.href="{{ route('wizard', 3) }}";
                                }, 3500);
                            }, function (error) {
                                $("#errorTitle").html("{{ __("Hata!") }}");
                                $("#errorMessage").html("{{ __("Kullanıcı oluşturulurken hata ile karşılaşıldı. Tekrar deneyiniz.") }}");
                                $("#error").fadeIn(300);
                            });
                        }
                    </script>
@endsection