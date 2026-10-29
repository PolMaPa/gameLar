@if (session('success'))
    <div role="status"
         class="fade-up mb-6 flex items-start gap-3 rounded-2xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200">
        <svg viewBox="0 0 20 20" fill="currentColor" class="mt-0.5 size-5 shrink-0" aria-hidden="true">
            <path fill-rule="evenodd"
                  d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z"
                  clip-rule="evenodd"/>
        </svg>
        <p class="pt-0.5">{{ session('success') }}</p>
    </div>
@endif

@if ($errors->any())
    <div role="alert"
         class="fade-up mb-6 flex items-start gap-3 rounded-2xl border border-rose-500/30 bg-rose-500/10 px-4 py-3 text-sm text-rose-200">
        <svg viewBox="0 0 20 20" fill="currentColor" class="mt-0.5 size-5 shrink-0" aria-hidden="true">
            <path fill-rule="evenodd"
                  d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495ZM10 5.75a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0v-3.5A.75.75 0 0 1 10 5.75Zm0 8a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z"
                  clip-rule="evenodd"/>
        </svg>
        <div>
            <p class="font-semibold">Revisa los siguientes campos</p>
            <ul class="mt-1.5 list-inside list-disc space-y-1 text-rose-200/90">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif
