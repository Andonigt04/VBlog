
export async function apiLogin({ email, passkey, onSuccess, onError }) {
	try {
		const res = await fetch('/api/login', {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'Accept': 'application/json',
			},
			body: JSON.stringify({ email, passkey })
		});
		const data = await res.json();
		if (data.redirect) {
			window.location.href = data.redirect;
		} else if (data.status !== 200) {
			if (onError) onError(data);
			else alert(data.message || 'Login failed');
		} else if (onSuccess) {
			onSuccess(data);
		}
	} catch (err) {
		if (onError) onError({ message: 'Error en el login' });
		else alert('Error en el login');
	}
}
