import random
import uuid
from locust import HttpUser, task, between

PER_PAGE = 50
MAX_PAGE = 500  # páginas aleatorias para no probar siempre la página 1


class ApiUser(HttpUser):
    wait_time = between(1, 3)

    def _get(self, path, name):
        page = random.randint(1, MAX_PAGE)
        url = f"{path}?page={page}&per_page={PER_PAGE}"
        with self.client.get(url, name=name, catch_response=True, timeout=30) as r:
            if r.status_code != 200:
                r.failure(f"HTTP {r.status_code}")
                return
            try:
                body = r.json()
            except ValueError:
                r.failure("Respuesta no es JSON")
                return
            if "data" not in body:
                r.failure("Falta la clave 'data'")
                return
            r.success()

    @task(5)
    def listado(self):
        self._get("/api/users", "GET /users")

    @task(3)
    def correos(self):
        self._get("/api/users/emails", "GET /users/emails")

    @task(3)
    def over_twenty(self):
        self._get("/api/users/over-twenty", "GET /users/over-twenty")

    @task(1)
    def bulk(self):
        payload = {
            "users": [
                {
                    "name": f"Test {i}",
                    "email": f"{uuid.uuid4().hex}@locust.test",
                    "birth_date": "1998-05-12",
                }
                for i in range(3)
            ]
        }
        with self.client.post(
            "/api/users/bulk",
            json=payload,
            headers={"Accept": "application/json"},
            name="POST /users/bulk",
            catch_response=True,
            timeout=30,
        ) as r:
            if r.status_code == 201:
                r.success()
            else:
                r.failure(f"Esperaba 201, recibí {r.status_code}")