$(document).ready(function () {
	// Registration form submission with AJAX
	$("#registerForm").on("submit", function (e) {
		e.preventDefault();
		if (!validateRegisterForm()) return;

		$.ajax({
			url: base_url + "auth/do_register",
			type: "POST",
			data: $(this).serialize(),
			dataType: "json",
			success: function (res) {
				if (res.status === "success") {
					$("#alert").html(
						'<div class="alert alert-success">' + res.message + "</div>",
					);
					$("#registerForm")[0].reset();
					setTimeout(function () {
						window.location.href = base_url + "auth/login";
					}, 3000);
				} else {
					$("#alert").html(
						'<div class="alert alert-danger">' + res.errors + "</div>",
					);
				}
			},
		});
	});

	// Email availability check via AJAX
	$("#email").on("blur", function () {
		let email = $(this).val();
        // alert(email);
		if (email && validateEmail(email)) {
			$.ajax({
				url: base_url + "auth/check_email",
				type: "POST",
				data: { email: email },
				dataType: "json",
				success: function (res) {
					if (res.exists) {
						$("#email").addClass("is-invalid");
						$("#emailError").text("Email already exists!");
					} else {
						$("#email").removeClass("is-invalid");
						$("#emailError").text("");
					}
				},
			});
		}
	});

	// Login form submission with AJAX
	$("#loginForm").on("submit", function (e) {
		e.preventDefault();
		if (!validateLoginForm()) return;

		$.ajax({
			url: base_url + "auth/do_login",
			type: "POST",
			data: $(this).serialize(),
			dataType: "json",
			success: function (res) {
				if (res.status === "success") {
					window.location.href = base_url + res.redirect;
				} else {
					$("#alert").html(
						'<div class="alert alert-danger">' + res.errors + "</div>",
					);
				}
			},
		});
	});

	// Dealer setup form submission
	$("#setupForm").on("submit", function (e) {
		e.preventDefault();
		$.ajax({
			url: base_url + "DealerSetup/save_setup",
			type: "POST",
			data: $(this).serialize(),
			dataType: "json",
			success: function (res) {
				if (res.status === "success") {
					window.location.href = base_url + res.redirect;
				} else {
					$("#alert").html(
						'<div class="alert alert-danger">' + res.errors + "</div>",
					);
				}
			},
		});
	});

	// Dealer update location
	$("#updateLocationForm").on("submit", function (e) {
		e.preventDefault();
		$.ajax({
			url: base_url + "dealer/update_location",
			type: "POST",
			data: $(this).serialize(),
			dataType: "json",
			success: function (res) {
				if (res.status === "success") {
					$("#alert").html(
						'<div class="alert alert-success">' + res.message + "</div>",
					);
				} else {
					$("#alert").html(
						'<div class="alert alert-danger">' + res.errors + "</div>",
					);
				}
			},
		});
	});

	// Edit dealer modal (Employee)
	$(".edit-dealer").on("click", function () {
		let id = $(this).data("id");
		let city = $(this).data("city");
		let state = $(this).data("state");
		let zip = $(this).data("zip");
		$("#dealer_id").val(id);
		$("#edit_city").val(city);
		$("#edit_state").val(state);
		$("#edit_zip").val(zip);
		$("#editModal").modal("show");
	});

	// Submit edit dealer form
	$("#editDealerForm").on("submit", function (e) {
		e.preventDefault();
		let dealerId = $("#dealer_id").val();
		$.ajax({
			url: base_url + "employee/edit_dealer/" + dealerId,
			type: "POST",
			data: $(this).serialize(),
			dataType: "json",
			success: function (res) {
				if (res.status === "success") {
					$("#editModal").modal("hide");
					$("#alert").html(
						'<div class="alert alert-success">' + res.message + "</div>",
					);
					setTimeout(function () {
						location.reload();
					}, 1500);
				} else {
					alert(res.errors);
				}
			},
		});
	});

	// Helper validation functions
	function validateRegisterForm() {
		let valid = true;
		if (!$("#first_name").val()) {
			$("#first_name").addClass("is-invalid");
			valid = false;
		}
		if (!$("#last_name").val()) {
			$("#last_name").addClass("is-invalid");
			valid = false;
		}
		let email = $("#email").val();
		if (!email || !validateEmail(email)) {
			$("#email").addClass("is-invalid");
			valid = false;
		}
		let pwd = $("#password").val();
		if (!pwd || pwd.length < 6) {
			$("#password").addClass("is-invalid");
			valid = false;
		}
		if (!$("#user_type").val()) {
			$("#user_type").addClass("is-invalid");
			valid = false;
		}
		return valid;
	}

	function validateLoginForm() {
		let valid = true;
		let email = $("#login_email").val();
		if (!email) {
			$("#login_email").addClass("is-invalid");
			valid = false;
		}
		if (!$("#password").val()) {
			$("#password").addClass("is-invalid");
			valid = false;
		}
		return valid;
	}

	function validateEmail(email) {
		let re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
		return re.test(email);
	}

	// Removed invalid class on input
	$("input, select").on("input change", function () {
		$(this).removeClass("is-invalid");
	});
});
