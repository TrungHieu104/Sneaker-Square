{{-- The fields every answer form shares. Expects $id (unique per page), $question, $answer, $choices from KnowledgeBase::scopeChoices(), and optionally $scope and $scopeId. --}}
@php
    $scope ??= \App\Models\AssistantKnowledgeModel::SCOPE_ALL;
    $scopeId ??= null;
@endphp
<label class="form-label" for="{{ $id }}-cau-hoi">Câu hỏi mẫu</label>
<input class="form-control mb-3" id="{{ $id }}-cau-hoi" name="question" maxlength="255" required value="{{ $question }}">
<label class="form-label" for="{{ $id }}-pham-vi">Áp dụng cho</label>
<select class="form-select mb-3" id="{{ $id }}-pham-vi" name="scope"
    onchange="this.form.scope_id.value = this.selectedOptions[0].dataset.id || ''">
    @foreach ($choices as $choice)
        <option value="{{ $choice['scope'] }}" data-id="{{ $choice['id'] }}" @selected($choice['scope'] === $scope && $choice['id'] === $scopeId)>{{ $choice['label'] }}</option>
    @endforeach
</select>
<input type="hidden" name="scope_id" value="{{ $scopeId }}">
<label class="form-label" for="{{ $id }}-tra-loi">Câu trả lời</label>
<textarea class="form-control" id="{{ $id }}-tra-loi" name="answer" rows="6" maxlength="2000" required>{{ $answer }}</textarea>
<div class="form-text mb-3">
    Trợ lý chỉ diễn đạt lại nội dung này cho khách, nên viết đúng sự thật và đủ ý. Nội dung do nhân viên chịu trách nhiệm.
</div>
