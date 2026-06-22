<?php

namespace App\Http\Controllers;

use App\Models\KpiCard;
use App\Models\KpiFormField;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class KpiCardController extends Controller
{
    public function index(Request $request)
    {
        $this->admin($request);
        $cards = KpiCard::withCount(['formFields', 'assignments', 'submissions'])->orderBy('display_order')->paginate(15);
        return view('kpi-cards.index', compact('cards'));
    }

    public function create(Request $request)
    {
        $this->admin($request);
        return view('kpi-cards.form', ['card' => new KpiCard]);
    }

    public function store(Request $request)
    {
        $this->admin($request);
        $data = $this->validated($request);
        $data['slug'] = $data['slug'] ?: Str::slug($data['title']);
        KpiCard::create($data);
        return redirect()->route('manage-kpis.index')->with('success', 'KPI card created successfully.');
    }

    public function edit(Request $request, KpiCard $kpi_card)
    {
        $this->admin($request);
        return view('kpi-cards.form', ['card' => $kpi_card->load('formFields')]);
    }

    public function update(Request $request, KpiCard $kpi_card)
    {
        $this->admin($request);
        $data = $this->validated($request, $kpi_card);
        $data['slug'] = $data['slug'] ?: Str::slug($data['title']);
        $kpi_card->update($data);
        return redirect()->route('manage-kpis.index')->with('success', 'KPI card updated successfully.');
    }

    public function storeField(Request $request, KpiCard $kpi_card)
    {
        $this->admin($request);
        $data = $request->validate([
            'field_label' => ['required', 'string', 'max:150'],
            'field_name' => ['required', 'alpha_dash', 'max:150', Rule::unique('kpi_form_fields')->where('kpi_card_id', $kpi_card->id)],
            'field_type' => ['required', Rule::in(['text', 'number', 'date', 'textarea', 'select', 'radio', 'checkbox'])],
            'options_text' => ['nullable', 'string'], 'is_required' => ['nullable', 'boolean'], 'sort_order' => ['required', 'integer', 'min:0'],
        ]);
        $data['options'] = in_array($data['field_type'], ['select', 'radio', 'checkbox']) ? collect(explode(',', $data['options_text'] ?? ''))->map->trim()->filter()->values()->all() : null;
        $data['is_required'] = $request->boolean('is_required');
        unset($data['options_text']);
        $kpi_card->formFields()->create($data);
        return back()->with('success', 'Dynamic form field added.');
    }

    public function destroyField(Request $request, KpiCard $kpi_card, KpiFormField $field)
    {
        $this->admin($request);
        abort_unless($field->kpi_card_id === $kpi_card->id, 404);
        $field->delete();
        return back()->with('success', 'Dynamic form field removed.');
    }

    private function validated(Request $request, ?KpiCard $card = null): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'slug' => ['nullable', 'string', 'max:160', Rule::unique('kpi_cards')->ignore($card)],
            'category' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'icon' => ['required', 'string', 'max:80'],
            'frequency' => ['required', Rule::in(['daily', 'weekly', 'monthly', 'yearly'])],
            'total_marks' => ['required', 'numeric', 'min:1'],
            'display_order' => ['required', 'integer', 'min:0'],
            'is_active' => ['required', 'boolean'],
        ]);
    }

    private function admin(Request $request): void
    {
        abort_unless($request->user()?->role?->slug === 'super_admin', 403);
    }
}
