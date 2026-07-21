<?php

namespace Tests\Feature;

use App\Models\Region;
use App\Models\TravelTip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Nested <form> tags are invalid HTML: the browser closes the outer form as
 * soon as it hits the inner form's closing tag, silently detaching every
 * field/button after it (e.g. "Änderungen speichern" stops submitting).
 * These tests parse the rendered HTML to make sure the KI-image mini-form
 * never ends up nested inside the main edit form again.
 */
class AdminEditFormHtmlTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'name' => 'Administrator', 'email' => 'admin@lieblingsorte.test',
            'password' => Hash::make('admin123'), 'role' => 'admin',
        ]);
    }

    private function assertNoNestedForms(string $html): void
    {
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_use_internal_errors(false);

        $forms = $dom->getElementsByTagName('form');
        foreach ($forms as $form) {
            $parent = $form->parentNode;
            while ($parent) {
                $this->assertNotSame(
                    'form',
                    $parent->nodeName,
                    'Found a <form> nested inside another <form> — this breaks the outer form in real browsers.'
                );
                $parent = $parent->parentNode;
            }
        }
    }

    public function test_region_edit_page_has_no_nested_forms_when_ai_image_form_is_shown(): void
    {
        config(['services.openai.key' => 'test-key']);

        $region = Region::create([
            'name' => 'Alpenpässe', 'type' => 'Reisegebiet', 'country' => 'Österreich',
            'short_description' => 'Kurz', 'description' => 'Lang', 'is_published' => false,
        ]);

        $response = $this->actingAs($this->admin())->get(route('admin.regions.edit', $region));

        $response->assertOk();
        $response->assertSee('Mit KI generieren');
        $this->assertNoNestedForms($response->getContent());
    }

    public function test_saving_region_changes_persists_even_with_ai_image_form_present(): void
    {
        config(['services.openai.key' => 'test-key']);

        $region = Region::create([
            'name' => 'Alpenpässe', 'type' => 'Reisegebiet', 'country' => 'Österreich',
            'federal_state' => 'Tirol', 'short_description' => 'Kurz', 'description' => 'Lang',
            'best_travel_time' => 'Mai bis Oktober', 'is_published' => false, 'sort_order' => 10,
        ]);

        $response = $this->actingAs($this->admin())->put(route('admin.regions.update', $region), [
            'name' => $region->name,
            'slug' => $region->slug,
            'type' => $region->type,
            'country' => $region->country,
            'federal_state' => $region->federal_state,
            'short_description' => $region->short_description,
            'description' => $region->description,
            'best_travel_time' => 'Juni bis September',
            'is_published' => '1',
            'sort_order' => $region->sort_order,
        ]);

        $response->assertRedirect(route('admin.regions.index'));
        $this->assertDatabaseHas('regions', ['id' => $region->id, 'best_travel_time' => 'Juni bis September', 'is_published' => true]);
    }

    public function test_tip_edit_page_has_no_nested_forms_when_ai_image_form_is_shown(): void
    {
        config(['services.openai.key' => 'test-key']);

        $region = Region::create([
            'name' => 'Alpenpässe', 'type' => 'Reisegebiet', 'country' => 'Österreich',
            'short_description' => 'Kurz', 'description' => 'Lang', 'is_published' => false,
        ]);
        $tip = TravelTip::create([
            'region_id' => $region->id, 'title' => 'Timmelsjoch',
            'short_description' => 'Kurz', 'description' => 'Lang', 'is_published' => false,
        ]);

        $response = $this->actingAs($this->admin())->get(route('admin.tips.edit', $tip));

        $response->assertOk();
        $response->assertSee('Mit KI generieren');
        $this->assertNoNestedForms($response->getContent());
    }
}
