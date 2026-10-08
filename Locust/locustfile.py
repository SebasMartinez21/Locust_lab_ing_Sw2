from locust import HttpUser, task

class HelloWorldUser(HttpUser): 
    @task
    def ejm1(self):
        self.client.get("/1")

    @task
    def ejm2(self):
        self.client.get("/2")