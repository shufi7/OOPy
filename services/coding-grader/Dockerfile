FROM python:3.13-slim
WORKDIR /grader
COPY sandbox.py checks.py /grader/
USER 65534:65534
ENTRYPOINT ["python", "-I", "-S", "-B", "/grader/sandbox.py"]
