import os, subprocess, textwrap
BASE = "/home/ngemis/cms-tsm/database/migrations"

M = {}

M["2026_01_01_000100_create_settings_table"] = r'''
Schema::create('settings', function (Blueprint $table) {
    $table->id();
    $table->string('key')->unique();
    $table->longText('value')->nullable();
    $table->string('group')->default('umum');
    $table->string('type')->default('text'); // text, textarea, html, image, number, boolean, json
    $table->string('label')->nullable();
    $table->text('description')->nullable();
    $table->timestamps();
});
'''

M["2026_01_01_000200_create_categories_table"] = r'''
Schema::create('categories', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('slug')->unique();
    $table->text('description')->nullable();
    $table->string('color')->default('#1e3a8a');
    $table->unsignedInteger('sort_order')->default(0);
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
'''

M["2026_01_01_000300_create_tags_table"] = r'''
Schema::create('tags', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('slug')->unique();
    $table->timestamps();
});
'''

M["2026_01_01_000400_create_posts_table"] = r'''
Schema::create('posts', function (Blueprint $table) {
    $table->id();
    $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
    $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
    $table->string('title');
    $table->string('slug')->unique();
    $table->text('excerpt')->nullable();
    $table->longText('body')->nullable();
    $table->string('cover_image')->nullable();
    $table->string('cover_alt')->nullable();
    $table->string('status')->default('draft'); // draft, published, scheduled, archived
    $table->timestamp('published_at')->nullable();
    $table->boolean('is_featured')->default(false);
    $table->boolean('allow_comments')->default(true);
    $table->unsignedBigInteger('views_count')->default(0);
    $table->unsignedBigInteger('likes_count')->default(0);
    $table->unsignedBigInteger('dislikes_count')->default(0);
    $table->unsignedBigInteger('shares_count')->default(0);
    $table->unsignedBigInteger('comments_count')->default(0);
    $table->unsignedInteger('reading_time')->default(1);
    // SEO
    $table->string('meta_title')->nullable();
    $table->string('meta_description', 500)->nullable();
    $table->string('focus_keyword')->nullable();
    $table->string('canonical_url')->nullable();
    $table->string('og_image')->nullable();
    $table->unsignedTinyInteger('seo_score')->default(0);
    $table->json('seo_report')->nullable();
    $table->timestamps();
    $table->index(['status', 'published_at']);
});
'''

M["2026_01_01_000500_create_post_tag_table"] = r'''
Schema::create('post_tag', function (Blueprint $table) {
    $table->foreignId('post_id')->constrained()->cascadeOnDelete();
    $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
    $table->primary(['post_id', 'tag_id']);
});
'''

M["2026_01_01_000600_create_post_reactions_table"] = r'''
Schema::create('post_reactions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('post_id')->constrained()->cascadeOnDelete();
    $table->string('visitor_hash', 64);
    $table->tinyInteger('value'); // 1 = like, -1 = dislike
    $table->timestamps();
    $table->unique(['post_id', 'visitor_hash']);
});
'''

M["2026_01_01_000700_create_comments_table"] = r'''
Schema::create('comments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('post_id')->constrained()->cascadeOnDelete();
    $table->foreignId('parent_id')->nullable()->constrained('comments')->cascadeOnDelete();
    $table->string('name');
    $table->string('email')->nullable();
    $table->text('body');
    $table->string('status')->default('pending'); // pending, approved, spam, rejected
    $table->string('ip_hash', 64)->nullable();
    $table->string('user_agent')->nullable();
    $table->unsignedInteger('likes_count')->default(0);
    $table->timestamps();
    $table->index(['post_id', 'status']);
});
'''

M["2026_01_01_000750_create_comment_reactions_table"] = r'''
Schema::create('comment_reactions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('comment_id')->constrained()->cascadeOnDelete();
    $table->string('visitor_hash', 64);
    $table->timestamps();
    $table->unique(['comment_id', 'visitor_hash']);
});
'''

M["2026_01_01_000800_create_visits_table"] = r'''
Schema::create('visits', function (Blueprint $table) {
    $table->id();
    $table->string('url', 500);
    $table->string('path', 255)->nullable();
    $table->foreignId('post_id')->nullable()->constrained()->nullOnDelete();
    $table->string('visitor_hash', 64)->nullable();
    $table->string('ip_hash', 64)->nullable();
    $table->string('referrer', 500)->nullable();
    $table->string('device', 50)->nullable();
    $table->string('browser', 100)->nullable();
    $table->string('country', 100)->nullable();
    $table->boolean('is_unique')->default(true);
    $table->date('visited_on')->nullable();
    $table->timestamps();
    $table->index(['visited_on']);
    $table->index(['path']);
});
'''

M["2026_01_01_000900_create_competencies_table"] = r'''
Schema::create('competencies', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->string('slug')->nullable();
    $table->string('icon')->default('wrench');
    $table->text('short_description')->nullable();
    $table->longText('description')->nullable();
    $table->string('image')->nullable();
    $table->unsignedInteger('sort_order')->default(0);
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
'''

M["2026_01_01_001000_create_programs_table"] = r'''
Schema::create('programs', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->string('subtitle')->nullable();
    $table->string('icon')->default('star');
    $table->longText('description')->nullable();
    $table->string('image')->nullable();
    $table->unsignedInteger('sort_order')->default(0);
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
'''

M["2026_01_01_001100_create_facilities_table"] = r'''
Schema::create('facilities', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('category')->nullable(); // Bengkel, Lab, Ruang Teori, Alat
    $table->text('description')->nullable();
    $table->string('image')->nullable();
    $table->unsignedInteger('quantity')->nullable();
    $table->string('condition')->nullable();
    $table->unsignedInteger('sort_order')->default(0);
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
'''

M["2026_01_01_001200_create_achievements_table"] = r'''
Schema::create('achievements', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->string('student_name')->nullable();
    $table->string('level')->nullable(); // Sekolah, Kota, Provinsi, Nasional, Internasional
    $table->string('rank')->nullable();
    $table->string('organizer')->nullable();
    $table->year('year')->nullable();
    $table->date('achieved_at')->nullable();
    $table->text('description')->nullable();
    $table->string('image')->nullable();
    $table->boolean('is_featured')->default(false);
    $table->unsignedInteger('sort_order')->default(0);
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
'''

M["2026_01_01_001300_create_testimonials_table"] = r'''
Schema::create('testimonials', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->year('graduation_year')->nullable();
    $table->string('occupation')->nullable();
    $table->string('workplace')->nullable();
    $table->text('message');
    $table->unsignedTinyInteger('rating')->default(5);
    $table->string('photo')->nullable();
    $table->string('status')->default('pending'); // pending, approved, rejected
    $table->boolean('is_featured')->default(false);
    $table->timestamps();
});
'''

M["2026_01_01_001400_create_teachers_table"] = r'''
Schema::create('teachers', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('position')->nullable();
    $table->string('subject')->nullable();
    $table->string('education')->nullable();
    $table->string('photo')->nullable();
    $table->string('email')->nullable();
    $table->text('bio')->nullable();
    $table->unsignedInteger('sort_order')->default(0);
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
'''

M["2026_01_01_001500_create_partners_table"] = r'''
Schema::create('partners', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('logo')->nullable();
    $table->string('website')->nullable();
    $table->string('category')->nullable(); // Industri, Prakerin, Rekrutmen
    $table->text('description')->nullable();
    $table->unsignedInteger('sort_order')->default(0);
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
'''

M["2026_01_01_001600_create_galleries_table"] = r'''
Schema::create('galleries', function (Blueprint $table) {
    $table->id();
    $table->string('title')->nullable();
    $table->string('type')->default('image'); // image, video
    $table->string('file')->nullable();
    $table->string('video_url')->nullable();
    $table->string('album')->nullable();
    $table->text('caption')->nullable();
    $table->unsignedInteger('sort_order')->default(0);
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
'''

M["2026_01_01_001700_create_sliders_table"] = r'''
Schema::create('sliders', function (Blueprint $table) {
    $table->id();
    $table->string('title')->nullable();
    $table->string('subtitle')->nullable();
    $table->string('image')->nullable();
    $table->string('button_text')->nullable();
    $table->string('button_url')->nullable();
    $table->unsignedInteger('sort_order')->default(0);
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
'''

M["2026_01_01_001800_create_contact_messages_table"] = r'''
Schema::create('contact_messages', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('email')->nullable();
    $table->string('phone')->nullable();
    $table->string('subject')->nullable();
    $table->text('message');
    $table->boolean('is_read')->default(false);
    $table->string('ip_hash', 64)->nullable();
    $table->timestamps();
});
'''

M["2026_01_01_001900_create_registrations_table"] = r'''
Schema::create('registrations', function (Blueprint $table) {
    $table->id();
    $table->string('registration_number')->unique();
    $table->string('full_name');
    $table->string('nisn')->nullable();
    $table->string('gender')->nullable();
    $table->date('birth_date')->nullable();
    $table->string('birth_place')->nullable();
    $table->text('address')->nullable();
    $table->string('phone')->nullable();
    $table->string('email')->nullable();
    $table->string('origin_school')->nullable();
    $table->string('parent_name')->nullable();
    $table->string('parent_phone')->nullable();
    $table->text('notes')->nullable();
    $table->string('status')->default('baru'); // baru, diproses, diterima, ditolak
    $table->timestamps();
});
'''

M["2026_01_01_002000_create_pages_table"] = r'''
Schema::create('pages', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->string('slug')->unique();
    $table->longText('body')->nullable();
    $table->string('meta_title')->nullable();
    $table->string('meta_description', 500)->nullable();
    $table->boolean('is_active')->default(true);
    $table->timestamps();
});
'''

M["2026_01_01_002100_add_fields_to_users_table"] = None  # custom below

tpl = '''<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{{
    public function up(): void
    {{
{up}
    }}

    public function down(): void
    {{
        Schema::dropIfExists('{table}');
    }}
}};
'''

for name, body in M.items():
    if body is None:
        continue
    table = name.split("create_")[1].rsplit("_table", 1)[0]
    up = textwrap.indent(body.strip("\n"), "        ")
    with open(os.path.join(BASE, name + ".php"), "w") as f:
        f.write(tpl.format(up=up, table=table))

users = '''<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('editor')->after('email'); // admin, editor
            $table->string('avatar')->nullable()->after('role');
            $table->boolean('is_active')->default(true)->after('avatar');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'avatar', 'is_active', 'last_login_at']);
        });
    }
};
'''
open(os.path.join(BASE, "2026_01_01_002100_add_fields_to_users_table.php"), "w").write(users)

print(subprocess.run(["ls", BASE], capture_output=True, text=True).stdout)
