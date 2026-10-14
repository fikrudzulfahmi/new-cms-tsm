<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Achievement;
use App\Models\Category;
use App\Models\Competency;
use App\Models\ContactMessage;
use App\Models\Facility;
use App\Models\Gallery;
use App\Models\Partner;
use App\Models\Program;
use App\Models\Registration;
use App\Models\Slider;
use App\Models\Teacher;
use App\Models\Testimonial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ContentController extends Controller
{
    // Generic store/update/destroy helpers
    private function storeModel($model, Request $request, array $rules, array $imageFields = [])
    {
        $data = $request->validate($rules);
        foreach ($imageFields as $field) {
            if ($request->hasFile($field)) {
                $data[$field] = $request->file($field)->store('content', 'public');
            }
        }
        $model::create($data);
        return back()->with('success', 'Data berhasil ditambahkan!');
    }

    private function updateModel($instance, Request $request, array $rules, array $imageFields = [])
    {
        $data = $request->validate($rules);
        foreach ($imageFields as $field) {
            if ($request->hasFile($field)) {
                $data[$field] = $request->file($field)->store('content', 'public');
            }
        }
        $instance->update($data);
        return back()->with('success', 'Data berhasil diperbarui!');
    }

    // --- Sliders ---
    public function sliders() { return Inertia::render('Admin/Content/Sliders', ['sliders' => Slider::orderBy('sort_order')->get()]); }
    public function slidersStore(Request $r) { return $this->storeModel(Slider::class, $r, ['title'=>'nullable|string|max:255','subtitle'=>'nullable|string|max:500','button_text'=>'nullable|string|max:100','button_url'=>'nullable|string|max:500','sort_order'=>'integer','is_active'=>'boolean'], ['image']); }
    public function slidersUpdate(Slider $slider, Request $r) { return $this->updateModel($slider, $r, ['title'=>'nullable|string|max:255','subtitle'=>'nullable|string|max:500','button_text'=>'nullable|string|max:100','button_url'=>'nullable|string|max:500','sort_order'=>'integer','is_active'=>'boolean'], ['image']); }
    public function slidersDestroy(Slider $slider) { $slider->delete(); return back()->with('success','Slider dihapus.'); }

    // --- Competencies ---
    public function competencies() { return Inertia::render('Admin/Content/Competencies', ['competencies' => Competency::orderBy('sort_order')->get()]); }
    public function competenciesStore(Request $r) { $d=$r->validate(['title'=>'required|string|max:255','icon'=>'nullable|string|max:100','short_description'=>'nullable|string|max:500','description'=>'nullable|string','sort_order'=>'integer','is_active'=>'boolean']); $d['slug']=Str::slug($d['title']); if($r->hasFile('image'))$d['image']=$r->file('image')->store('content','public'); Competency::create($d); return back()->with('success','Kompetensi ditambahkan!'); }
    public function competenciesUpdate(Competency $competency, Request $r) { $d=$r->validate(['title'=>'required|string|max:255','icon'=>'nullable|string|max:100','short_description'=>'nullable|string|max:500','description'=>'nullable|string','sort_order'=>'integer','is_active'=>'boolean']); $d['slug']=Str::slug($d['title']); if($r->hasFile('image'))$d['image']=$r->file('image')->store('content','public'); $competency->update($d); return back()->with('success','Kompetensi diperbarui!'); }
    public function competenciesDestroy(Competency $competency) { $competency->delete(); return back()->with('success','Data dihapus.'); }

    // --- Programs ---
    public function programs() { return Inertia::render('Admin/Content/Programs', ['programs' => Program::orderBy('sort_order')->get()]); }
    public function programsStore(Request $r) { return $this->storeModel(Program::class, $r, ['title'=>'required|string|max:255','subtitle'=>'nullable|string|max:255','icon'=>'nullable|string|max:100','description'=>'nullable|string','sort_order'=>'integer','is_active'=>'boolean'], ['image']); }
    public function programsUpdate(Program $program, Request $r) { return $this->updateModel($program, $r, ['title'=>'required|string|max:255','subtitle'=>'nullable|string|max:255','icon'=>'nullable|string|max:100','description'=>'nullable|string','sort_order'=>'integer','is_active'=>'boolean'], ['image']); }
    public function programsDestroy(Program $program) { $program->delete(); return back()->with('success','Data dihapus.'); }

    // --- Facilities ---
    public function facilities() { return Inertia::render('Admin/Content/Facilities', ['facilities' => Facility::orderBy('sort_order')->get()]); }
    public function facilitiesStore(Request $r) { return $this->storeModel(Facility::class, $r, ['name'=>'required|string|max:255','category'=>'nullable|string|max:100','description'=>'nullable|string','quantity'=>'nullable|integer','condition'=>'nullable|string|max:100','sort_order'=>'integer','is_active'=>'boolean'], ['image']); }
    public function facilitiesUpdate(Facility $facility, Request $r) { return $this->updateModel($facility, $r, ['name'=>'required|string|max:255','category'=>'nullable|string|max:100','description'=>'nullable|string','quantity'=>'nullable|integer','condition'=>'nullable|string|max:100','sort_order'=>'integer','is_active'=>'boolean'], ['image']); }
    public function facilitiesDestroy(Facility $facility) { $facility->delete(); return back()->with('success','Data dihapus.'); }

    // --- Achievements ---
    public function achievements() { return Inertia::render('Admin/Content/Achievements', ['achievements' => Achievement::orderByDesc('year')->orderBy('sort_order')->get()]); }
    public function achievementsStore(Request $r) { return $this->storeModel(Achievement::class, $r, ['title'=>'required|string|max:255','student_name'=>'nullable|string|max:200','level'=>'nullable|string|max:100','rank'=>'nullable|string|max:100','organizer'=>'nullable|string|max:200','year'=>'nullable|integer|min:2000','achieved_at'=>'nullable|date','description'=>'nullable|string','is_featured'=>'boolean','sort_order'=>'integer','is_active'=>'boolean'], ['image']); }
    public function achievementsUpdate(Achievement $achievement, Request $r) { return $this->updateModel($achievement, $r, ['title'=>'required|string|max:255','student_name'=>'nullable|string|max:200','level'=>'nullable|string|max:100','rank'=>'nullable|string|max:100','organizer'=>'nullable|string|max:200','year'=>'nullable|integer|min:2000','achieved_at'=>'nullable|date','description'=>'nullable|string','is_featured'=>'boolean','sort_order'=>'integer','is_active'=>'boolean'], ['image']); }
    public function achievementsDestroy(Achievement $achievement) { $achievement->delete(); return back()->with('success','Data dihapus.'); }

    // --- Testimonials ---
    public function testimonials() { return Inertia::render('Admin/Content/Testimonials', ['testimonials' => Testimonial::latest()->get()]); }
    public function testimonialsUpdate(Testimonial $testimonial, Request $r) { $d=$r->validate(['status'=>'required|in:pending,approved,rejected','is_featured'=>'boolean']); $testimonial->update($d); return back()->with('success','Testimoni diperbarui!'); }
    public function testimonialsDestroy(Testimonial $testimonial) { $testimonial->delete(); return back()->with('success','Testimoni dihapus.'); }

    // --- Teachers ---
    public function teachers() { return Inertia::render('Admin/Content/Teachers', ['teachers' => Teacher::orderBy('sort_order')->get()]); }
    public function teachersStore(Request $r) { return $this->storeModel(Teacher::class, $r, ['name'=>'required|string|max:200','position'=>'nullable|string|max:200','subject'=>'nullable|string|max:200','education'=>'nullable|string|max:255','email'=>'nullable|email|max:150','bio'=>'nullable|string','sort_order'=>'integer','is_active'=>'boolean'], ['photo']); }
    public function teachersUpdate(Teacher $teacher, Request $r) { return $this->updateModel($teacher, $r, ['name'=>'required|string|max:200','position'=>'nullable|string|max:200','subject'=>'nullable|string|max:200','education'=>'nullable|string|max:255','email'=>'nullable|email|max:150','bio'=>'nullable|string','sort_order'=>'integer','is_active'=>'boolean'], ['photo']); }
    public function teachersDestroy(Teacher $teacher) { $teacher->delete(); return back()->with('success','Data dihapus.'); }

    // --- Partners ---
    public function partners() { return Inertia::render('Admin/Content/Partners', ['partners' => Partner::orderBy('sort_order')->get()]); }
    public function partnersStore(Request $r) { return $this->storeModel(Partner::class, $r, ['name'=>'required|string|max:200','website'=>'nullable|url','category'=>'nullable|string|max:100','description'=>'nullable|string','sort_order'=>'integer','is_active'=>'boolean'], ['logo']); }
    public function partnersUpdate(Partner $partner, Request $r) { return $this->updateModel($partner, $r, ['name'=>'required|string|max:200','website'=>'nullable|url','category'=>'nullable|string|max:100','description'=>'nullable|string','sort_order'=>'integer','is_active'=>'boolean'], ['logo']); }
    public function partnersDestroy(Partner $partner) { $partner->delete(); return back()->with('success','Data dihapus.'); }

    // --- Gallery ---
    public function gallery() { return Inertia::render('Admin/Content/Gallery', ['galleries' => Gallery::orderBy('sort_order')->get()]); }
    public function galleryStore(Request $r) { $d=$r->validate(['title'=>'nullable|string|max:255','type'=>'required|in:image,video','video_url'=>'nullable|url','album'=>'nullable|string|max:100','caption'=>'nullable|string','sort_order'=>'integer','is_active'=>'boolean']); if($r->hasFile('file'))$d['file']=$r->file('file')->store('gallery','public'); Gallery::create($d); return back()->with('success','Media ditambahkan!'); }
    public function galleryUpdate(Gallery $gallery, Request $r) { $d=$r->validate(['title'=>'nullable|string|max:255','type'=>'required|in:image,video','video_url'=>'nullable|url','album'=>'nullable|string|max:100','caption'=>'nullable|string','sort_order'=>'integer','is_active'=>'boolean']); if($r->hasFile('file'))$d['file']=$r->file('file')->store('gallery','public'); $gallery->update($d); return back()->with('success','Media diperbarui!'); }
    public function galleryDestroy(Gallery $gallery) { $gallery->delete(); return back()->with('success','Media dihapus.'); }

    // --- Categories ---
    public function categories() { return Inertia::render('Admin/Content/Categories', ['categories' => Category::withCount('posts')->orderBy('sort_order')->get()]); }
    public function categoriesStore(Request $r) { $d=$r->validate(['name'=>'required|string|max:100','description'=>'nullable|string','color'=>'nullable|string|max:20','sort_order'=>'integer','is_active'=>'boolean']); $d['slug']=Str::slug($d['name']); Category::create($d); return back()->with('success','Kategori ditambahkan!'); }
    public function categoriesUpdate(Category $category, Request $r) { $d=$r->validate(['name'=>'required|string|max:100','description'=>'nullable|string','color'=>'nullable|string|max:20','sort_order'=>'integer','is_active'=>'boolean']); $d['slug']=Str::slug($d['name']); $category->update($d); return back()->with('success','Kategori diperbarui!'); }
    public function categoriesDestroy(Category $category) { $category->delete(); return back()->with('success','Kategori dihapus.'); }

    // --- Contact Messages ---
    public function messages() { return Inertia::render('Admin/Content/Messages', ['messages' => \App\Models\ContactMessage::latest()->paginate(20)]); }
    public function messagesRead(\App\Models\ContactMessage $message) { $message->update(['is_read'=>true]); return back(); }
    public function messagesDestroy(\App\Models\ContactMessage $message) { $message->delete(); return back()->with('success','Pesan dihapus.'); }

    // --- Registrations ---
    public function registrations(Request $r) { $q=\App\Models\Registration::latest(); if($r->status)$q->where('status',$r->status); return Inertia::render('Admin/Content/Registrations', ['registrations'=>$q->paginate(20)->withQueryString(),'filters'=>$r->only(['status'])]); }
    public function registrationsUpdate(\App\Models\Registration $registration, Request $r) { $registration->update($r->validate(['status'=>'required|in:baru,diproses,diterima,ditolak','notes'=>'nullable|string'])); return back()->with('success','Status pendaftaran diperbarui!'); }
    public function registrationsDestroy(\App\Models\Registration $registration) { $registration->delete(); return back()->with('success','Data dihapus.'); }
}
