<x-app-layout>
    <x-slot name="title">{{ __('Import Students') }}</x-slot>

    <div class="kia-breadcrumb">
        <a href="{{ route('students.index') }}">{{ __('Students') }}</a>
        <span class="sep">/</span>
        <span>{{ __('Import') }}</span>
    </div>

    <div class="kia-page-header">
        <h1 class="kia-page-title">{{ __('Import Students from Excel') }}</h1>
    </div>

    <div class="kia-card" style="max-width:760px;margin-bottom:20px;">
        <div class="kia-card-header">
            <h2 class="kia-card-title">{{ __('Step 1 — Download the Template') }}</h2>
        </div>
        <div class="kia-card-body">
            <p style="margin:0 0 12px;color:var(--muted);font-size:.875rem;">
                {{ __('Start from the official template so your columns and headers match exactly what the importer expects.') }}
            </p>
            <a href="{{ route('students.import.template') }}" class="btn btn-outline">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right:6px;display:inline;vertical-align:-3px;">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                    <polyline points="7 10 12 15 17 10"/>
                    <line x1="12" y1="15" x2="12" y2="3"/>
                </svg>
                {{ __('Download Template (.xlsx)') }}
            </a>
        </div>
    </div>

    <div class="kia-card" style="max-width:760px;">
        <div class="kia-card-header">
            <h2 class="kia-card-title">{{ __('Step 2 — Upload Student Data') }}</h2>
        </div>
        <div class="kia-card-body">
            @if($errors->any())
            <div class="alert alert-danger">
                <ul style="margin:0;padding-left:20px;">
                    @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
            @endif

            @if(session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
            @endif

            @if(session('errors'))
            <div class="alert alert-warning">
                <h4>{{ __('Import completed with errors') }}:</h4>
                <ul style="margin:0;padding-left:20px;font-size:0.9rem;">
                    @foreach(session('errors') as $error)
                    <li><strong>{{ __('Row') }} {{ $error['row'] }}:</strong> {{ $error['message'] }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <form method="POST" action="{{ route('students.import.store') }}" enctype="multipart/form-data">
                @csrf

                <div class="form-group" style="margin-bottom:2rem;">
                    <label class="form-label">{{ __('Excel File') }} <span class="req">*</span></label>
                    <div class="file-input-wrapper" style="border:2px dashed #ccc;padding:2rem;border-radius:0.5rem;text-align:center;cursor:pointer;">
                        <input type="file" id="file" name="file" class="form-control" 
                               accept=".xlsx,.xls,.csv" 
                               required
                               style="display:none;">
                        <label for="file" style="cursor:pointer;margin:0;">
                            <svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" style="margin:0 auto 1rem;opacity:0.5;">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                <polyline points="17 8 12 3 7 8"/>
                                <line x1="12" y1="3" x2="12" y2="15"/>
                            </svg>
                            <div>
                                <p style="margin:0.5rem 0;font-weight:500;">{{ __('Click to upload or drag and drop') }}</p>
                                <p style="margin:0.25rem 0;font-size:0.875rem;opacity:0.6;">{{ __('Supported formats: .xlsx, .xls, .csv') }}</p>
                            </div>
                        </label>
                        <div id="file-name" style="margin-top:1rem;font-size:0.875rem;color:#666;"></div>
                    </div>
                    @error('file')<span class="invalid-feedback">{{ $message }}</span>@enderror
                </div>

                <div class="form-info" style="background:var(--paper);padding:1rem;border-radius:0.5rem;margin-bottom:2rem;">
                    <h3 style="margin:0 0 0.5rem 0;font-size:0.95rem;">{{ __('Excel File Format') }}</h3>
                    <p style="margin:0.5rem 0;font-size:0.875rem;">{{ __('Your Excel file should have the following columns:') }}</p>
                    <ul style="margin:0.5rem 0;padding-left:20px;font-size:0.875rem;">
                        <li><strong>student_code</strong> (required) - Unique student ID</li>
                        <li><strong>name_en</strong> (required) - Student name in English</li>
                        <li><strong>name_km</strong> (optional) - Student name in Khmer</li>
                        <li><strong>gender</strong> (optional) - male, female, or other</li>
                        <li><strong>date_of_birth</strong> (optional) - Date in format YYYY-MM-DD or DD/MM/YYYY</li>
                        <li><strong>address</strong> (optional) - Student address</li>
                        <li><strong>class_name</strong> (optional) - Must exactly match an existing class name, e.g. "Grade 5"</li>
                        <li><strong>section_name</strong> (optional) - Must exactly match an existing section name within that class, e.g. "Section A". Both class_name and section_name must be given together to assign a class/section.</li>
                    </ul>
                </div>

                <div style="display:flex;gap:0.5rem;">
                    <button type="submit" class="btn btn-primary">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="margin-right:0.5rem;display:inline;">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                            <polyline points="17 8 12 3 7 8"/>
                            <line x1="12" y1="3" x2="12" y2="15"/>
                        </svg>
                        {{ __('Import Students') }}
                    </button>
                    <a href="{{ route('students.index') }}" class="btn btn-outline">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        const fileInput = document.getElementById('file');
        const fileInputWrapper = document.querySelector('.file-input-wrapper');
        const fileName = document.getElementById('file-name');

        // Handle click
        fileInputWrapper.addEventListener('click', () => fileInput.click());

        // Handle file selection
        fileInput.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                fileName.textContent = '📄 ' + e.target.files[0].name;
            }
        });

        // Handle drag and drop
        fileInputWrapper.addEventListener('dragover', (e) => {
            e.preventDefault();
            fileInputWrapper.style.backgroundColor = '#f0f0f0';
        });

        fileInputWrapper.addEventListener('dragleave', () => {
            fileInputWrapper.style.backgroundColor = 'transparent';
        });

        fileInputWrapper.addEventListener('drop', (e) => {
            e.preventDefault();
            fileInputWrapper.style.backgroundColor = 'transparent';
            if (e.dataTransfer.files.length > 0) {
                fileInput.files = e.dataTransfer.files;
                fileName.textContent = '📄 ' + e.dataTransfer.files[0].name;
            }
        });
    </script>
</x-app-layout>
