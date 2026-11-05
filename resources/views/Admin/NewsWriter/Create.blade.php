@extends('admin.layouts.app')

@section('content')
<div class="p-6 max-w-2xl mx-auto">
    <div class="bg-white rounded-2xl p-8 shadow-sm border">
        <h2 class="text-2xl font-bold text-navy mb-6">Buat Akun Penulis Baru</h2>
        
        @if(session('flash')?.[0]?.success)
        <div class="bg-green-100 border-green-400 text-green-700 rounded px-4 py-3 mb-4" role="alert">
            {{ session('flash')[0].success }}
        </div>
        @endif
        
        <form wire:submit="store" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Nama Lengkap</label>
                <input type="text" name="name" wire:model.debounce.300="name" required
                       class="w-full rounded border border-slate-300 px-3 py-2 focus:outline-none focus:border-accent transition-colors">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Email</label>
                <input type="email" name="email" wire:model.debounce.300="email" required
                       class="w-full rounded border border-slate-300 px-3 py-2 focus:outline-none focus:border-accent transition-colors">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Kata Sandi</label>
                <input type="password" name="password" required
                       class="w-full rounded border border-slate-300 px-3 py-2 focus:outline-none focus:border-accent transition-colors">
                <p class="text-xs text-slate-500">Minimal 8 karakter</p>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Role</label>
                    <select name="role" wire:model.debounce.300="role" class="w-full rounded border border-slate-300 px-3 py-2 focus:outline-none focus:border-accent transition-colors">
                        <option value="writer">Penulis</option>
                        <!-- role admin dan editor diasetui dari halaman pengguna lain -->
                    </select>
                </div>
            </div>
            
            <div>
                <button type="submit"
                        class="w-full flex justify-center py-3 px-6 rounded-full text-white font-medium text-sm transition-colors bg-accent hover:bg-opacity-90">
                    Buat Akun Penulis
                </button>
            </div>
        </form>
    </div>
</div>
@endsection