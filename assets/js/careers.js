async function nakHrOpenModal(contentHtml) {
  const existing = document.querySelector('.nak-hr-careers-modal');
  if (existing) existing.remove();

  const modal = document.createElement('div');
  modal.className = 'nak-hr-careers-modal';
  modal.innerHTML = '<div class="nak-hr-careers-modal__dialog">' + contentHtml + '</div>';
  document.body.appendChild(modal);
  document.body.classList.add('nak-hr-modal-open');
  return modal;
}

document.addEventListener('DOMContentLoaded', async () => {
  const quizRoot = document.querySelector('[data-quiz-popup-root]');

  if (!quizRoot) return;

  const ajaxUrl = quizRoot.getAttribute('data-ajax-url');
  const nonce = quizRoot.getAttribute('data-nonce');

  if (!ajaxUrl || !nonce) return;

  try {
    const response = await fetch(ajaxUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
      },
      body: new URLSearchParams({
        action: 'nak_hr_get_quiz_popup',
        nonce,
      }),
    });

    const payload = await response.json();

    if (payload.success && payload?.data?.html) {
      const modal = await nakHrOpenModal(payload.data.html);
      modal.querySelector('.nak-hr-careers-modal__dialog').innerHTML = payload.data.html;
    }
  } catch (error) {
    console.error('Quiz popup failed to load.', error);
  }
});

document.addEventListener('click', async (event) => {
  const trigger = event.target.closest('.nak-hr-career-card[data-career-id]');
  const wikiOpen = event.target.closest('[data-wiki-open]');
  const wikiDelete = event.target.closest('[data-wiki-delete]');
  const careerFormOpen = event.target.closest('[data-career-form-open]');
  const employeeFormOpen = event.target.closest('[data-employee-form-open]');
  const close = event.target.closest('[data-careers-close]');
  const backdrop = event.target.classList.contains('nak-hr-careers-modal');

  if (close || backdrop) {
    const modal = document.querySelector('.nak-hr-careers-modal');
    if (modal) modal.remove();
    document.body.classList.remove('nak-hr-modal-open');
    return;
  }

  if (wikiDelete) {
    const questionId = wikiDelete.getAttribute('data-question-id');
    const ajaxUrl = wikiDelete.getAttribute('data-ajax-url');
    const nonce = wikiDelete.getAttribute('data-nonce');

    if (!questionId || !ajaxUrl || !nonce) return;
    if (!window.confirm('Delete this question?')) return;

    const response = await fetch(ajaxUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
      },
      body: new URLSearchParams({
        action: 'nak_hr_delete_wiki_question',
        nonce,
        question_id: questionId,
      }),
    });

    const payload = await response.json();

    if (!payload.success) {
      window.alert(payload?.data?.message || 'Failed to delete question.');
      return;
    }

    const item = wikiDelete.closest('[data-question-id]');
    if (item) item.remove();
    return;
  }

  if (employeeFormOpen) {
    const employeeId = employeeFormOpen.getAttribute('data-employee-id') || '';
    const ajaxUrl = employeeFormOpen.getAttribute('data-ajax-url');
    const nonce = employeeFormOpen.getAttribute('data-nonce');

    if (!ajaxUrl || !nonce) return;

    const modal = await nakHrOpenModal('<div class="nak-hr-careers-modal__loading">Loading...</div>');

    try {
      const response = await fetch(ajaxUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
        },
        body: new URLSearchParams({
          action: 'nak_hr_get_employee_form',
          nonce,
          employee_id: employeeId,
        }),
      });

      const payload = await response.json();

      if (!payload.success) {
        throw new Error(payload?.data?.message || 'Failed to load employee form.');
      }

      modal.querySelector('.nak-hr-careers-modal__dialog').innerHTML = payload.data.html;
    } catch (error) {
      modal.querySelector('.nak-hr-careers-modal__dialog').innerHTML = '<div class="nak-hr-careers-modal__content"><button type="button" class="nak-hr-careers-modal__close" data-careers-close>&times;</button><p>' + error.message + '</p></div>';
    }
    return;
  }

  if (careerFormOpen) {
    const careerId = careerFormOpen.getAttribute('data-career-id') || '';
    const ajaxUrl = careerFormOpen.getAttribute('data-ajax-url');
    const nonce = careerFormOpen.getAttribute('data-nonce');

    if (!ajaxUrl || !nonce) return;

    const modal = await nakHrOpenModal('<div class="nak-hr-careers-modal__loading">Loading...</div>');

    try {
      const response = await fetch(ajaxUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
        },
        body: new URLSearchParams({
          action: 'nak_hr_get_career_form',
          nonce,
          career_id: careerId,
        }),
      });

      const payload = await response.json();

      if (!payload.success) {
        throw new Error(payload?.data?.message || 'Failed to load position form.');
      }

      modal.querySelector('.nak-hr-careers-modal__dialog').innerHTML = payload.data.html;
    } catch (error) {
      modal.querySelector('.nak-hr-careers-modal__dialog').innerHTML = '<div class="nak-hr-careers-modal__content"><button type="button" class="nak-hr-careers-modal__close" data-careers-close>&times;</button><p>' + error.message + '</p></div>';
    }
    return;
  }

  if (wikiOpen) {
    const questionId = wikiOpen.getAttribute('data-question-id') || '';
    const ajaxUrl = wikiOpen.getAttribute('data-ajax-url');
    const nonce = wikiOpen.getAttribute('data-nonce');

    if (!ajaxUrl || !nonce) return;

    const modal = await nakHrOpenModal('<div class="nak-hr-careers-modal__loading">Loading...</div>');

    try {
      const response = await fetch(ajaxUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
        },
        body: new URLSearchParams({
          action: 'nak_hr_get_wiki_form',
          nonce,
          question_id: questionId,
        }),
      });

      const payload = await response.json();

      if (!payload.success) {
        throw new Error(payload?.data?.message || 'Failed to load question form.');
      }

      modal.querySelector('.nak-hr-careers-modal__dialog').innerHTML = payload.data.html;
    } catch (error) {
      modal.querySelector('.nak-hr-careers-modal__dialog').innerHTML = '<div class="nak-hr-careers-modal__content"><button type="button" class="nak-hr-careers-modal__close" data-careers-close>&times;</button><p>' + error.message + '</p></div>';
    }
    return;
  }

  if (!trigger) return;

  const careerId = trigger.getAttribute('data-career-id');
  const ajaxUrl = trigger.getAttribute('data-ajax-url');
  const nonce = trigger.getAttribute('data-nonce');

  if (!careerId || !ajaxUrl || !nonce) return;

  const modal = await nakHrOpenModal('<div class="nak-hr-careers-modal__loading">Loading...</div>');

  try {
    const response = await fetch(ajaxUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
      },
      body: new URLSearchParams({
        action: 'nak_hr_get_career',
        nonce,
        career_id: careerId,
      }),
    });

    const payload = await response.json();

    if (!payload.success) {
      throw new Error(payload?.data?.message || 'Failed to load position details.');
    }

    modal.querySelector('.nak-hr-careers-modal__dialog').innerHTML = payload.data.html;
  } catch (error) {
    modal.querySelector('.nak-hr-careers-modal__dialog').innerHTML = '<div class="nak-hr-careers-modal__content"><button type="button" class="nak-hr-careers-modal__close" data-careers-close>&times;</button><p>' + error.message + '</p></div>';
  }
});

document.addEventListener('submit', async (event) => {
  const form = event.target.closest('[data-career-apply]');
  const wikiForm = event.target.closest('[data-wiki-form]');
  const careerManageForm = event.target.closest('[data-career-manage-form]');
  const employeeManageForm = event.target.closest('[data-employee-manage-form]');
  const quizPopupForm = event.target.closest('[data-quiz-popup-form]');

  if (quizPopupForm) {
    event.preventDefault();

    const feedback = quizPopupForm.querySelector('[data-quiz-popup-feedback]');
    const submitButton = quizPopupForm.querySelector('button[type="submit"]');
    const formData = new FormData(quizPopupForm);
    const quizRoot = document.querySelector('[data-quiz-popup-root]');
    const ajaxUrl = quizRoot?.getAttribute('data-ajax-url');

    if (!ajaxUrl) return;

    if (feedback) {
      feedback.textContent = 'Submitting...';
      feedback.className = 'nak-hr-careers-apply-feedback';
    }

    if (submitButton) {
      submitButton.disabled = true;
      submitButton.setAttribute('disabled', 'disabled');
      submitButton.setAttribute('aria-disabled', 'true');
      submitButton.classList.add('is-disabled');
    }

    try {
      const response = await fetch(ajaxUrl, {
        method: 'POST',
        body: formData,
      });

      const payload = await response.json();

      if (!payload.success) {
        throw new Error(payload?.data?.message || 'Failed to submit quiz answers.');
      }

      if (feedback) {
        feedback.textContent = payload.data.message;
        feedback.className = 'nak-hr-careers-apply-feedback is-success';
      }
    } catch (error) {
      if (feedback) {
        feedback.textContent = error.message;
        feedback.className = 'nak-hr-careers-apply-feedback is-error';
      }
    } finally {
      if (submitButton) {
        submitButton.disabled = false;
        submitButton.removeAttribute('disabled');
        submitButton.removeAttribute('aria-disabled');
        submitButton.classList.remove('is-disabled');
      }
    }
    return;
  }

  if (employeeManageForm) {
    event.preventDefault();

    const feedback = employeeManageForm.querySelector('[data-employee-manage-feedback]');
    const submitButton = employeeManageForm.querySelector('button[type="submit"]');
    const formData = new FormData(employeeManageForm);
    const ajaxUrl = document.querySelector('[data-employee-form-open][data-ajax-url]')?.getAttribute('data-ajax-url');

    if (!ajaxUrl) return;

    if (feedback) {
      feedback.textContent = 'Saving...';
      feedback.className = 'nak-hr-careers-apply-feedback';
    }

    if (submitButton) {
      submitButton.disabled = true;
      submitButton.setAttribute('disabled', 'disabled');
      submitButton.setAttribute('aria-disabled', 'true');
      submitButton.classList.add('is-disabled');
    }

    try {
      const response = await fetch(ajaxUrl, {
        method: 'POST',
        body: formData,
      });

      const payload = await response.json();

      if (!payload.success) {
        throw new Error(payload?.data?.message || 'Failed to save employee.');
      }

      if (feedback) {
        feedback.textContent = payload.data.message;
        feedback.className = 'nak-hr-careers-apply-feedback is-success';
      }

      window.setTimeout(() => {
        window.location.reload();
      }, 500);
    } catch (error) {
      if (feedback) {
        feedback.textContent = error.message;
        feedback.className = 'nak-hr-careers-apply-feedback is-error';
      }
    } finally {
      if (submitButton) {
        submitButton.disabled = false;
        submitButton.removeAttribute('disabled');
        submitButton.removeAttribute('aria-disabled');
        submitButton.classList.remove('is-disabled');
      }
    }
    return;
  }

  if (careerManageForm) {
    event.preventDefault();

    const feedback = careerManageForm.querySelector('[data-career-manage-feedback]');
    const submitButton = careerManageForm.querySelector('button[type="submit"]');
    const formData = new FormData(careerManageForm);
    const ajaxUrl = document.querySelector('[data-career-form-open][data-ajax-url]')?.getAttribute('data-ajax-url');

    if (!ajaxUrl) return;

    if (feedback) {
      feedback.textContent = 'Saving...';
      feedback.className = 'nak-hr-careers-apply-feedback';
    }

    if (submitButton) {
      submitButton.disabled = true;
      submitButton.setAttribute('disabled', 'disabled');
      submitButton.setAttribute('aria-disabled', 'true');
      submitButton.classList.add('is-disabled');
    }

    try {
      const response = await fetch(ajaxUrl, {
        method: 'POST',
        body: formData,
      });

      const payload = await response.json();

      if (!payload.success) {
        throw new Error(payload?.data?.message || 'Failed to save position.');
      }

      if (feedback) {
        feedback.textContent = payload.data.message;
        feedback.className = 'nak-hr-careers-apply-feedback is-success';
      }

      window.setTimeout(() => {
        window.location.reload();
      }, 500);
    } catch (error) {
      if (feedback) {
        feedback.textContent = error.message;
        feedback.className = 'nak-hr-careers-apply-feedback is-error';
      }
    } finally {
      if (submitButton) {
        submitButton.disabled = false;
        submitButton.removeAttribute('disabled');
        submitButton.removeAttribute('aria-disabled');
        submitButton.classList.remove('is-disabled');
      }
    }
    return;
  }

  if (wikiForm) {
    event.preventDefault();

    const feedback = wikiForm.querySelector('[data-wiki-feedback]');
    const submitButton = wikiForm.querySelector('button[type="submit"]');
    const formData = new FormData(wikiForm);
    const ajaxUrl = document.querySelector('[data-wiki-open][data-ajax-url]')?.getAttribute('data-ajax-url');

    if (!ajaxUrl) return;

    if (feedback) {
      feedback.textContent = 'Saving...';
      feedback.className = 'nak-hr-careers-apply-feedback';
    }

    if (submitButton) {
      submitButton.disabled = true;
      submitButton.setAttribute('disabled', 'disabled');
      submitButton.setAttribute('aria-disabled', 'true');
      submitButton.classList.add('is-disabled');
    }

    try {
      const response = await fetch(ajaxUrl, {
        method: 'POST',
        body: formData,
      });

      const payload = await response.json();

      if (!payload.success) {
        throw new Error(payload?.data?.database_error || payload?.data?.message || 'Failed to save question.');
      }

      if (feedback) {
        feedback.textContent = payload.data.database_error?payload.data.database_error:payload.data.message;
        feedback.className = 'nak-hr-careers-apply-feedback is-success';
      }

      window.location.reload();
    } catch (error) {
      if (feedback) {
        feedback.textContent = error.database_error?error.database_error:error.message;
        feedback.className = 'nak-hr-careers-apply-feedback is-error';
      }
    } finally {
      if (submitButton) {
        submitButton.disabled = false;
        submitButton.removeAttribute('disabled');
        submitButton.removeAttribute('aria-disabled');
        submitButton.classList.remove('is-disabled');
      }
    }
    return;
  }

  if (!form) return;

  event.preventDefault();

  const feedback = form.querySelector('[data-career-feedback]');
  const submitButton = form.querySelector('button[type="submit"]');
  const formData = new FormData(form);
  const ajaxUrl = document.querySelector('.nak-hr-career-card[data-ajax-url]')?.getAttribute('data-ajax-url');

  if (!ajaxUrl) return;

  if (feedback) {
    feedback.textContent = 'Submitting...';
    feedback.className = 'nak-hr-careers-apply-feedback';
  }

  if (submitButton) submitButton.disabled = true;

  try {
    const response = await fetch(ajaxUrl, {
      method: 'POST',
      body: formData,
    });

    const payload = await response.json();

    if (!payload.success) {
      throw new Error(payload?.data?.message || 'Failed to submit application.');
    }

    if (feedback) {
      feedback.textContent = payload.data.message;
      feedback.className = 'nak-hr-careers-apply-feedback is-success';
    }

    form.reset();
  } catch (error) {
    if (feedback) {
      feedback.textContent = error.message;
      feedback.className = 'nak-hr-careers-apply-feedback is-error';
    }
  } finally {
    if (submitButton) submitButton.disabled = false;
  }
});

document.addEventListener('keydown', (event) => {
  if (event.key !== 'Escape') return;
  const modal = document.querySelector('.nak-hr-careers-modal');
  if (!modal) return;
  modal.remove();
  document.body.classList.remove('nak-hr-modal-open');
});
