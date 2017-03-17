# Description

A Docker container for an open source Just-In-Time Capture Origin featuring:

- Just-In-Time creation of an VOD from Live stream (HLS) ready for consumption.
- Option to remove segments between CUE IN/OUT markers and inserting discontinuity markers in the generated VOD.

The JIT Capture Origin Container is built on:

- Ubuntu
- Apache2
- hlsorigin (https://pypi.python.org/pypi/hlsorigin)

# Usage

Run the following command to start up the container assuming that the media segments will be stored on 
the disk mounted at `/mnt/media`

```
docker run -d -v /mnt/media:/data -p 3000:80 --restart=always --name jit-capture eyevinntechnology/jit-capture:0.0.1
```

In this example we are running the jit-capture container on port 3000. To use port 80 you would instead run:

```
docker run -d -v /mnt/media:/data -p 80:80 --restart=always --name jit-capture eyevinntechnology/jit-capture:0.0.1
```

Then configure your encoder to push HLS to:

```
http://jit-capture-server.example.com/ingest/event/
```

This container does not manage the disk space so you would need a process that reclaim disk space when the 
media store `/mnt/media` is getting full.