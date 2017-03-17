# Description

A Docker container for an open source Just-In-Time Capture Origin featuring:

- Just-In-Time creation of an VOD from Live stream (HLS) ready for consumption.
- Option to remove segments between CUE IN/OUT markers and inserting discontinuity markers in the generated VOD.

The JIT Capture Origin Container is built on:

- Ubuntu 16
- Apache2.4
- hlsorigin (https://pypi.python.org/pypi/hlsorigin)

